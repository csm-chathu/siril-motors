<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'origin_country'];
    public function models() { return $this->hasMany(VehicleModel::class); }
    public function vehicleTypes() { return $this->belongsToMany(VehicleType::class, 'models'); }
}
