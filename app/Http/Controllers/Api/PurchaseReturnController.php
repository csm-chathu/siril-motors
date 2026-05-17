<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        $user    = request()->user();
        $returns = PurchaseReturn::with([
                'supplier:id,name',
                'purchase:id,purchase_number',
                'user:id,name',
                'journalEntry:id,entry_number',
                'items.product:id,name,sku',
            ])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when(request('supplier_id'), fn($q, $s) => $q->where('supplier_id', $s))
            ->when(request('from_date'), fn($q, $d) => $q->whereDate('return_date', '>=', $d))
            ->when(request('to_date'),   fn($q, $d) => $q->whereDate('return_date', '<=', $d))
            ->latest('return_date')
            ->paginate(request('per_page', 25));

        return response()->json($returns);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'purchase_id'   => 'nullable|exists:purchases,id',
            'return_date'   => 'required|date',
            'reason'        => 'required|in:damaged,wrong_item,excess_quantity,quality_issue,other',
            'credit_method' => 'required|in:ap_credit,cash_refund,bank_refund',
            'notes'         => 'nullable|string',
            'items'         => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_cost'  => 'required|numeric|min:0',
            'items.*.reason_note'=> 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $total = collect($data['items'])->sum(fn($i) => $i['unit_cost'] * $i['quantity']);
            $seq   = PurchaseReturn::whereDate('created_at', today())->count() + 1;

            $return = PurchaseReturn::create([
                'return_number' => 'PR-' . now()->format('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
                'purchase_id'   => $data['purchase_id'] ?? null,
                'supplier_id'   => $data['supplier_id'],
                'return_date'   => $data['return_date'],
                'reason'        => $data['reason'],
                'total'         => $total,
                'credit_method' => $data['credit_method'],
                'notes'         => $data['notes'] ?? null,
                'branch_id'     => $request->user()->branch_id,
                'user_id'       => $request->user()->id,
            ]);

            foreach ($data['items'] as $row) {
                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id'  => $row['product_id'],
                    'quantity'    => $row['quantity'],
                    'unit_cost'   => $row['unit_cost'],
                    'total'       => $row['unit_cost'] * $row['quantity'],
                    'reason_note' => $row['reason_note'] ?? null,
                ]);

                // Reduce stock
                Product::where('id', $row['product_id'])
                    ->decrement('stock_quantity', $row['quantity']);
            }

            $entry = $this->postJournal($return);
            $return->update(['journal_entry_id' => $entry->id]);

            AuditLog::record('purchase_return_created', "Return {$return->return_number} created", $return);
            DB::commit();

            return response()->json($return->load(['supplier', 'purchase', 'items.product', 'journalEntry']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        return response()->json($purchaseReturn->load(['supplier', 'purchase', 'items.product', 'user', 'journalEntry']));
    }

    public function destroy(PurchaseReturn $purchaseReturn)
    {
        // Restore stock before deleting
        foreach ($purchaseReturn->items as $item) {
            Product::where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
        }
        $purchaseReturn->delete();
        return response()->json(['message' => 'Return deleted']);
    }

    private function postJournal(PurchaseReturn $return): JournalEntry
    {
        $inventory = Account::where('code', '1200')->first();
        $ap        = Account::where('code', '2000')->first();
        $cash      = Account::where('code', '1000')->first();
        $bank      = Account::where('code', '1010')->first();

        if (!$inventory) throw new \Exception('Inventory account (1200) not found.');

        $creditAcct = match ($return->credit_method) {
            'ap_credit'   => $ap,
            'cash_refund' => $cash,
            'bank_refund' => $bank,
            default       => null,
        };
        if (!$creditAcct) throw new \Exception('Credit account not found for selected method.');

        $seq   = JournalEntry::whereDate('created_at', today())->withTrashed()->count() + 1;
        $entry = JournalEntry::create([
            'entry_number'   => 'JE-' . date('Ymd') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT),
            'entry_date'     => $return->return_date,
            'description'    => "Purchase return {$return->return_number}",
            'reference_type' => 'PurchaseReturn',
            'reference_id'   => $return->id,
            'branch_id'      => $return->branch_id,
            'created_by'     => auth()->id(),
            'status'         => 'posted',
        ]);

        // Dr AP / Cash / Bank — supplier owes us or refunds us
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $creditAcct->id,
            'debit'            => $return->total,
            'credit'           => 0,
            'description'      => "Return credit — {$return->return_number}",
        ]);

        // Cr Inventory — stock leaves the building
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id'       => $inventory->id,
            'debit'            => 0,
            'credit'           => $return->total,
            'description'      => "Inventory reduced — {$return->return_number}",
        ]);

        return $entry;
    }
}
