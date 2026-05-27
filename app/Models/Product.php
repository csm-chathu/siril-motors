<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_type_id', 'brand_id', 'model_id', 'part_category_id', 'quality_type_id',
        'supplier_id', 'branch_id', 'sku', 'barcode', 'name', 'part_number', 'description',
        'purchase_price', 'selling_price', 'stock_quantity',
        'min_stock_level', 'rack_location', 'is_active', 'image', 'image_public_id',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'purchase_price' => 'float',
        'selling_price'  => 'float',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function model()
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function partCategory()
    {
        return $this->belongsTo(PartCategory::class);
    }

    public function qualityType()
    {
        return $this->belongsTo(QualityType::class);
    }

    public function getLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->min_stock_level;
    }
}
