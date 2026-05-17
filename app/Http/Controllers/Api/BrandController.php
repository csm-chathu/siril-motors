<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::with('models:id,name,brand_id,vehicle_type_id')
            ->when(request('vehicle_type_id'), fn($q, $v) => $q->whereHas('models', fn($q2) => $q2->where('vehicle_type_id', $v)))
            ->orderBy('name')
            ->get();
        return response()->json($brands);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100|unique:brands,name',
            'description'    => 'nullable|string|max:500',
            'origin_country' => 'nullable|string|max:100',
        ]);
        return response()->json(Brand::create($data), 201);
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100|unique:brands,name,' . $brand->id,
            'description'    => 'nullable|string|max:500',
            'origin_country' => 'nullable|string|max:100',
        ]);
        $brand->update($data);
        return response()->json($brand);
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
