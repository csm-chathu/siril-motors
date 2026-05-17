<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number', 'supplier_id', 'purchase_id', 'payment_date',
        'amount', 'payment_method', 'cheque_number', 'cheque_date',
        'cheque_bank_name', 'reference', 'notes',
        'journal_entry_id', 'branch_id', 'user_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'cheque_date'  => 'date',
        'amount'       => 'float',
    ];

    public function supplier()    { return $this->belongsTo(Supplier::class); }
    public function purchase()    { return $this->belongsTo(Purchase::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function branch()      { return $this->belongsTo(Branch::class); }
    public function journalEntry(){ return $this->belongsTo(JournalEntry::class); }
}
