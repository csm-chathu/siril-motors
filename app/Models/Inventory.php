<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = ['spare_part_id', 'quantity', 'location'];

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }
}