<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleModel;
use Illuminate\Http\Request;

class VehicleModelController extends Controller
{
    public function index()
    {
        $models = VehicleModel::with(['brand:id,name', 'vehicleType:id,name'])
            ->when(request('brand_id'),        fn($q, $b) => $q->where('brand_id', $b))
            ->when(request('vehicle_type_id'), fn($q, $v) => $q->where('vehicle_type_id', $v))
            ->orderBy('name')
            ->get();
        return response()->json($models);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'brand_id'        => 'required|exists:brands,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'year_from'       => 'nullable|integer|min:1900',
            'year_to'         => 'nullable|integer|min:1900',
        ]);
        $model = VehicleModel::create($data);
        return response()->json($model->load(['brand:id,name', 'vehicleType:id,name']), 201);
    }

    public function update(Request $request, VehicleModel $model)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'brand_id'        => 'required|exists:brands,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'year_from'       => 'nullable|integer|min:1900',
            'year_to'         => 'nullable|integer|min:1900',
        ]);
        $model->update($data);
        return response()->json($model->load(['brand:id,name', 'vehicleType:id,name']));
    }

    public function destroy(VehicleModel $model)
    {
        $model->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
