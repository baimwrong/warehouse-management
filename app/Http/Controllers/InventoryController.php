<?php

namespace App\Http\Controllers;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($sub) use ($search, $like) {
                $sub->whereHas('product', function ($q) use ($search, $like) {
                    $q->where('name', $like, "%{$search}%")
                      ->orWhere('sku', $like, "%{$search}%");
                })->orWhereHas('location', function ($q) use ($search, $like) {
                    $q->where('location_code', $like, "%{$search}%");
                });
            });
        }

        $stocks = $query->orderBy('warehouse_id')->paginate(20)->withQueryString();

        return view('inventory.index', compact('stocks', 'warehouses', 'warehouseId', 'search'));
    }

    public function lowStock()
    {
        $products = Product::where('is_active', true)
            ->with(['category', 'stocks.warehouse', 'stocks.location'])
            ->leftJoin('inventory_stocks', 'products.id', '=', 'inventory_stocks.product_id')
            ->select('products.*')
            ->selectRaw('COALESCE(SUM(inventory_stocks.quantity_on_hand), 0) as total_on_hand')
            ->groupBy('products.id')
            ->havingRaw('COALESCE(SUM(inventory_stocks.quantity_on_hand), 0) <= products.reorder_point')
            ->orderBy('total_on_hand')
            ->paginate(20);

        return view('inventory.low-stock', compact('products'));
    }

    public function batches(Request $request)
    {
        $days = (int) $request->query('days', 90);

        $query = ProductBatch::with(['product.category', 'supplier', 'stocks.warehouse', 'stocks.location'])
            ->whereNotNull('expiry_date');

        if ($days > 0) {
            $query->where('expiry_date', '<=', Carbon::now()->addDays($days));
        }

        $batches = $query->orderBy('expiry_date')->paginate(20)->withQueryString();

        return view('inventory.batches', compact('batches', 'days'));
    }
}
