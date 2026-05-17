<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'return_number', 'purchase_id', 'supplier_id', 'return_date',
        'reason', 'total', 'credit_method', 'notes',
        'journal_entry_id', 'branch_id', 'user_id',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total'       => 'float',
    ];

    public function purchase()    { return $this->belongsTo(Purchase::class); }
    public function supplier()    { return $this->belongsTo(Supplier::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function branch()      { return $this->belongsTo(Branch::class); }
    public function journalEntry(){ return $this->belongsTo(JournalEntry::class); }
    public function items()       { return $this->hasMany(PurchaseReturnItem::class); }
}
