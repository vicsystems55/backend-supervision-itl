<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of all warehouses.
     */
    public function index()
    {
        $warehouses = Warehouse::orderBy('name')->get();
        return response()->json($warehouses);
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'code'            => 'nullable|string|max:50|unique:warehouses,code',
            'address'         => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:100',
            'state'           => 'nullable|string|max:100',
            'country'         => 'nullable|string|max:100',
            'contact_person'  => 'nullable|string|max:100',
            'contact_phone'   => 'nullable|string|max:20',
            'contact_email'   => 'nullable|email|max:100',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'description'     => 'nullable|string',
            'active'          => 'boolean',
        ]);

        $warehouse = Warehouse::create($validated);

        return response()->json([
            'message' => 'Warehouse created successfully.',
            'data' => $warehouse
        ], 201);
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Warehouse $warehouse)
    {
        return response()->json($warehouse);
    }

    /**
     * Update the specified warehouse in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name'            => 'sometimes|required|string|max:255',
            'code'            => 'nullable|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'address'         => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:100',
            'state'           => 'nullable|string|max:100',
            'country'         => 'nullable|string|max:100',
            'contact_person'  => 'nullable|string|max:100',
            'contact_phone'   => 'nullable|string|max:20',
            'contact_email'   => 'nullable|email|max:100',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'description'     => 'nullable|string',
            'active'          => 'boolean',
        ]);

        $warehouse->update($validated);

        return response()->json([
            'message' => 'Warehouse updated successfully.',
            'data' => $warehouse
        ]);
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return response()->json([
            'message' => 'Warehouse deleted successfully.'
        ]);
    }
}
