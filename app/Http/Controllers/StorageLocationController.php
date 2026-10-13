<?php

namespace App\Http\Controllers;

use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use Illuminate\Http\Request;

class StorageLocationController extends Controller
{
    public function index(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $zoneId = $request->query('zone_id');
        $status = $request->query('status'); // occupied, vacant
        $search = $request->query('search');

        $warehouses = Warehouse::orderBy('name')->get();
        $zones = $warehouseId ? WarehouseZone::where('warehouse_id', $warehouseId)->get() : WarehouseZone::all();

        $query = StorageLocation::with(['warehouse', 'zone', 'stocks.product']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($zoneId) {
            $query->where('warehouse_zone_id', $zoneId);
        }
        if ($status === 'occupied') {
            $query->where('is_occupied', true);
        } elseif ($status === 'vacant') {
            $query->where('is_occupied', false);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('location_code', 'ilike', "%{$search}%")
                  ->orWhere('aisle', 'ilike', "%{$search}%")
                  ->orWhere('rack', 'ilike', "%{$search}%")
                  ->orWhere('bin', 'ilike', "%{$search}%");
            });
        }

        $locations = $query->orderBy('location_code')->paginate(20)->withQueryString();

        return view('locations.index', compact('locations', 'warehouses', 'zones', 'warehouseId', 'zoneId', 'status', 'search'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('status', 'active')->with('zones')->get();
        return view('locations.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'warehouse_zone_id' => 'required|exists:warehouse_zones,id',
            'location_code' => 'required|string|max:50|unique:storage_locations,location_code',
            'aisle' => 'nullable|string|max:20',
            'rack' => 'nullable|string|max:20',
            'shelf' => 'nullable|string|max:20',
            'bin' => 'nullable|string|max:20',
            'max_weight_kg' => 'required|numeric|min:0.01',
            'max_volume_cbm' => 'required|numeric|min:0.01',
        ]);

        $location = StorageLocation::create($validated);

        return redirect()->route('locations.index', ['warehouse_id' => $location->warehouse_id])
            ->with('success', "Storage Bin '{$location->location_code}' created successfully.");
    }

    public function visualMap(Request $request)
    {
        $warehouses = Warehouse::where('status', 'active')->get();
        $selectedWarehouse = $request->has('warehouse_id')
            ? Warehouse::find($request->query('warehouse_id'))
            : $warehouses->first();

        $zones = $selectedWarehouse ? $selectedWarehouse->zones()->with(['storageLocations.stocks.product'])->get() : collect();

        return view('locations.visual-map', compact('warehouses', 'selectedWarehouse', 'zones'));
    }
}
