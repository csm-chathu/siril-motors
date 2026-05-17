<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'product_id', 'quantity', 'unit_cost', 'selling_price', 'total',
        'batch_number', 'expiry_date', 'ordered_quantity', 'received_quantity',
    ];

    protected $casts = [
        'unit_cost'         => 'float',
        'selling_price'     => 'float',
        'total'             => 'float',
        'expiry_date'       => 'date',
        'ordered_quantity'  => 'integer',
        'received_quantity' => 'integer',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
