<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id', 'product_id', 'quantity', 'unit_cost', 'total', 'reason_note',
    ];

    protected $casts = [
        'unit_cost' => 'float',
        'total'     => 'float',
    ];

    public function purchaseReturn() { return $this->belongsTo(PurchaseReturn::class); }
    public function product()        { return $this->belongsTo(Product::class); }
}
