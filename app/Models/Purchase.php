<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'purchase_number', 'supplier_id', 'user_id', 'subtotal',
        'tax', 'total', 'status', 'payment_method',
        'cheque_number', 'cheque_date', 'cheque_bank_name',
        'cheque_settled_at', 'settlement_journal_id',
        'credit_due_date', 'credit_settled_at', 'credit_settlement_journal_id',
        'notes', 'supplier_ref', 'expected_delivery', 'journal_entry_id', 'purchased_at',
    ];

    protected $casts = [
        'purchased_at'      => 'datetime',
        'cheque_date'       => 'date',
        'cheque_settled_at' => 'datetime',
        'expected_delivery' => 'date',
        'credit_due_date'   => 'date',
        'credit_settled_at' => 'datetime',
        'subtotal'         => 'float',
        'tax'              => 'float',
        'total'            => 'float',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
