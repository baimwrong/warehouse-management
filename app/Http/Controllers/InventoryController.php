<?php

namespace App\Http\Controllers;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $search = $request->query('search');

        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();

        $query = InventoryStock::with(['warehouse', 'location.zone', 'product.category', 'batch'])
            ->where('quantity_on_hand', '>', 0);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($search) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('sku', 'ilike', "%{$search}%");
            })->orWhereHas('location', function ($q) use ($search) {
                $q->where('location_code', 'ilike', "%{$search}%");
            });
        }

        $stocks = $query->orderBy('warehouse_id')->paginate(20)->withQueryString();

        return view('inventory.index', compact('stocks', 'warehouses', 'warehouseId', 'search'));
    }

    public function lowStock()
    {
        $products = Product::where('is_active', true)
            ->with(['category', 'stocks.warehouse', 'stocks.location'])
            ->get()
            ->filter(fn($p) => $p->total_quantity_on_hand <= $p->reorder_point)
            ->sortBy('total_quantity_on_hand');

        return view('inventory.low-stock', compact('products'));
    }

    public function batches(Request $request)
    {
        $days = (int) $request->query('days', 90);

        $batches = ProductBatch::with(['product.category', 'supplier', 'stocks.warehouse', 'stocks.location'])
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date')
            ->paginate(20);

        return view('inventory.batches', compact('batches', 'days'));
    }
}
