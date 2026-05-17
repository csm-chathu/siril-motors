<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartCategory;
use Illuminate\Http\Request;

class PartCategoryController extends Controller
{
    public function index()
    {
        return response()->json(PartCategory::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:part_categories,name',
            'description' => 'nullable|string|max:500',
        ]);
        return response()->json(PartCategory::create($data), 201);
    }

    public function update(Request $request, PartCategory $partCategory)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:part_categories,name,' . $partCategory->id,
            'description' => 'nullable|string|max:500',
        ]);
        $partCategory->update($data);
        return response()->json($partCategory);
    }

    public function destroy(PartCategory $partCategory)
    {
        $partCategory->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
