<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $payments = SupplierPayment::with([
                'supplier:id,name',
                'purchase:id,purchase_number',
                'user:id,name',
                'journalEntry:id,entry_number',
            ])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when(request('supplier_id'), fn($q, $s) => $q->where('supplier_id', $s))
            ->when(request('from_date'), fn($q, $d) => $q->whereDate('payment_date', '>=', $d))
            ->when(request('to_date'),   fn($q, $d) => $q->whereDate('payment_date', '<=', $d))
            ->when(request('payment_method'), fn($q, $m) => $q->where('payment_method', $m))
            ->latest('payment_date')
            ->paginate(request('per_page', 25));

        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'purchase_id'    => 'nullable|exists:purchases,id',
            'payment_date'   => 'required|date',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,cheque',
            'cheque_number'  => 'nullable|required_if:payment_method,cheque|string|max:50',
            'cheque_date'    => 'nullable|required_if:payment_method,cheque|date',
            'cheque_bank_name' => 'nullable|string|max:100',
            'reference'      => 'nullable|string|max:100',
            'notes'          => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $seq     = SupplierPayment::whereDate('created_at', today())->count() + 1;
            $payment = SupplierPayment::create([
                ...$data,
                'payment_number' => 'SP-' . now()->format('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
                'branch_id'      => $request->user()->branch_id,
                'user_id'        => $request->user()->id,
            ]);

            $entry = $this->postJournal($payment);
            $payment->update(['journal_entry_id' => $entry->id]);

            AuditLog::record('supplier_payment_created', "Payment {$payment->payment_number} to supplier", $payment);
            DB::commit();

            return response()->json($payment->load(['supplier', 'purchase', 'journalEntry']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(SupplierPayment $supplierPayment)
    {
        $supplierPayment->delete();
        return response()->json(['message' => 'Payment deleted']);
    }

    private function postJournal(SupplierPayment $payment): JournalEntry
    {
        $ap      = Account::where('code', '1200')->first(); // placeholder – actually AP
        $ap      = Account::where('code', '2000')->first();
        $payAcct = match ($payment->payment_method) {
            'cash'          => Account::where('code', '1000')->first(),
            'bank_transfer' => Account::where('code', '1010')->first(),
            'cheque'        => Account::where('code', '1010')->first(), // direct bank debit for cheque payments out
            default         => null,
        };

        if (!$ap)      throw new \Exception('Accounts Payable account (2000) not found.');
        if (!$payAcct) throw new \Exception('Payment account not found.');

        $seq   = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
        $entry = JournalEntry::create([
            'entry_number'   => 'JE-' . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
            'entry_date'     => $payment->payment_date,
            'description'    => "Supplier payment {$payment->payment_number}",
            'reference_type' => 'SupplierPayment',
            'reference_id'   => $payment->id,
            'branch_id'      => $payment->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        // Dr AP — reduces what we owe the supplier
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $ap->id,
            'debit'            => $payment->amount,
            'credit'           => 0,
            'description'      => "Supplier payment — {$payment->payment_number}",
        ]);

        // Cr Cash / Bank
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $payAcct->id,
            'debit'            => 0,
            'credit'           => $payment->amount,
            'description'      => "Paid to supplier — {$payment->payment_number}",
        ]);

        return $entry;
    }
}
