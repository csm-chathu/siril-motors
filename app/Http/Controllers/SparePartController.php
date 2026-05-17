<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use Illuminate\Http\Request;

class SparePartController extends Controller
{
    public function index()
    {
        return SparePart::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        return SparePart::create($validated);
    }

    public function update(Request $request, SparePart $sparePart)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'quantity' => 'sometimes|integer',
            'supplier_id' => 'sometimes|exists:suppliers,id',
        ]);

        $sparePart->update($validated);

        return $sparePart;
    }

    public function destroy(SparePart $sparePart)
    {
        $sparePart->delete();

        return response()->noContent();
    }
}