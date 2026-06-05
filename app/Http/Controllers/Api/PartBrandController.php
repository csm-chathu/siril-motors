<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartBrand;
use Illuminate\Http\Request;

class PartBrandController extends Controller
{
    public function index()
    {
        return response()->json(PartBrand::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:part_brands,name',
            'description' => 'nullable|string|max:500',
        ]);
        return response()->json(PartBrand::create($data), 201);
    }

    public function update(Request $request, PartBrand $partBrand)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:part_brands,name,' . $partBrand->id,
            'description' => 'nullable|string|max:500',
        ]);
        $partBrand->update($data);
        return response()->json($partBrand);
    }

    public function destroy(PartBrand $partBrand)
    {
        $partBrand->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
