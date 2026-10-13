<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseZone;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::withCount(['zones', 'storageLocations'])
            ->with(['zones'])
            ->orderBy('name')
            ->paginate(12);

        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('warehouses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:warehouses,code',
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:30',
            'country' => 'required|string|max:100',
            'contact_email' => 'nullable|email|max:150',
            'contact_phone' => 'nullable|string|max:50',
            'capacity_cbm' => 'required|numeric|min:0',
            'status' => 'required|string|in:active,inactive,maintenance',
        ]);

        $warehouse = Warehouse::create($validated);

        // Automatically provision standard default zones for the new warehouse
        $defaultZones = [
            ['code' => 'ZN-REC', 'name' => 'Inbound Receiving', 'zone_type' => 'receiving', 'description' => 'Unloading and staging area'],
            ['code' => 'ZN-STOR-A', 'name' => 'General Storage A', 'zone_type' => 'storage', 'description' => 'Main pallet rack storage'],
            ['code' => 'ZN-STOR-B', 'name' => 'High-Density Storage B', 'zone_type' => 'storage', 'description' => 'Small parts and shelving'],
            ['code' => 'ZN-PACK', 'name' => 'Packing & QC', 'zone_type' => 'packing', 'description' => 'Packing tables and labeling'],
            ['code' => 'ZN-SHIP', 'name' => 'Outbound Staging & Dispatch', 'zone_type' => 'dispatch', 'description' => 'Staged for carrier pickup'],
        ];

        foreach ($defaultZones as $zone) {
            $warehouse->zones()->create($zone);
        }

        return redirect()->route('warehouses.show', $warehouse)
            ->with('success', "Warehouse '{$warehouse->name}' created with default operational zones.");
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load(['zones.storageLocations', 'users']);
        
        $totalBins = $warehouse->storageLocations()->count();
        $occupiedBins = $warehouse->storageLocations()->where('is_occupied', true)->count();
        $totalSkus = $warehouse->inventoryStocks()->distinct('product_id')->count('product_id');
        $totalItems = (float) $warehouse->inventoryStocks()->sum('quantity_on_hand');

        return view('warehouses.show', compact('warehouse', 'totalBins', 'occupiedBins', 'totalSkus', 'totalItems'));
    }

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'code' => "required|string|max:30|unique:warehouses,code,{$warehouse->id}",
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:30',
            'country' => 'required|string|max:100',
            'contact_email' => 'nullable|email|max:150',
            'contact_phone' => 'nullable|string|max:50',
            'capacity_cbm' => 'required|numeric|min:0',
            'status' => 'required|string|in:active,inactive,maintenance',
        ]);

        $warehouse->update($validated);

        return redirect()->route('warehouses.show', $warehouse)
            ->with('success', "Warehouse '{$warehouse->name}' updated successfully.");
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->inventoryStocks()->where('quantity_on_hand', '>', 0)->exists()) {
            return back()->withErrors(['error' => 'Cannot delete warehouse that still holds inventory stock. Transfer stock first.']);
        }

        $warehouse->delete();
        return redirect()->route('warehouses.index')->with('success', 'Warehouse deleted successfully.');
    }
}
