<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'invoice_number', 'customer_id', 'user_id', 'subtotal',
        'discount', 'tax', 'tax_rate', 'maintenance_amount', 'total',
        'payment_method', 'payment_status', 'sale_type', 'delivery_status',
        'booking_expires_at', 'delivered_at', 'amount_paid', 'notes',
        'is_draft', 'journal_entry_id', 'sold_at', 'view_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->view_token)) {
                $model->view_token = Str::random(32);
            }
        });
    }

    protected $casts = [
        'sold_at'            => 'datetime',
        'booking_expires_at' => 'date',
        'delivered_at'       => 'datetime',
        'subtotal'            => 'float',
        'discount'            => 'float',
        'tax'                 => 'float',
        'tax_rate'            => 'float',
        'maintenance_amount'  => 'float',
        'total'               => 'float',
        'amount_paid'         => 'float',
        'is_draft'            => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
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
