<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QualityType;
use Illuminate\Http\Request;

class QualityTypeController extends Controller
{
    public function index()
    {
        return response()->json(QualityType::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:quality_types,name',
            'description' => 'nullable|string|max:500',
        ]);
        return response()->json(QualityType::create($data), 201);
    }

    public function update(Request $request, QualityType $qualityType)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:quality_types,name,' . $qualityType->id,
            'description' => 'nullable|string|max:500',
        ]);
        $qualityType->update($data);
        return response()->json($qualityType);
    }

    public function destroy(QualityType $qualityType)
    {
        $qualityType->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
