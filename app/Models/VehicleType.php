<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];
    public function brands() { return $this->hasMany(Brand::class); }
    public function models() { return $this->hasMany(VehicleModel::class); }
}
