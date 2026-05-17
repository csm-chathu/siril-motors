<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\Request;

class VehicleTypeController extends Controller
{
    public function index()
    {
        return response()->json(VehicleType::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:vehicle_types,name',
            'description' => 'nullable|string|max:500',
        ]);
        return response()->json(VehicleType::create($data), 201);
    }

    public function update(Request $request, VehicleType $vehicleType)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:vehicle_types,name,' . $vehicleType->id,
            'description' => 'nullable|string|max:500',
        ]);
        $vehicleType->update($data);
        return response()->json($vehicleType);
    }

    public function destroy(VehicleType $vehicleType)
    {
        $vehicleType->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
