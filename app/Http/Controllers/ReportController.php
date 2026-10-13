<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function valuation(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $categoryId = $request->query('category_id');

        $warehouses = Warehouse::where('status', 'active')->get();
        $categories = Category::orderBy('name')->get();

        $query = DB::table('inventory_stocks')
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->join('warehouses', 'inventory_stocks.warehouse_id', '=', 'warehouses.id')
            ->select(
                'products.id as product_id',
                'products.sku',
                'products.name as product_name',
                'categories.name as category_name',
                'warehouses.name as warehouse_name',
                'products.uom',
                'products.unit_cost',
                'products.unit_price',
                DB::raw('SUM(inventory_stocks.quantity_on_hand) as total_qty'),
                DB::raw('SUM(inventory_stocks.quantity_on_hand * products.unit_cost) as total_cost_value'),
                DB::raw('SUM(inventory_stocks.quantity_on_hand * products.unit_price) as total_sales_value')
            )
            ->groupBy(
                'products.id',
                'products.sku',
                'products.name',
                'categories.name',
                'warehouses.name',
                'products.uom',
                'products.unit_cost',
                'products.unit_price'
            )
            ->havingRaw('SUM(inventory_stocks.quantity_on_hand) > 0');

        if ($warehouseId) {
            $query->where('inventory_stocks.warehouse_id', $warehouseId);
        }
        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        $records = $query->paginate(25)->withQueryString();

        $totalValuation = (float) DB::table('inventory_stocks')
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->when($warehouseId, fn($q) => $q->where('inventory_stocks.warehouse_id', $warehouseId))
            ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId))
            ->selectRaw('COALESCE(SUM(inventory_stocks.quantity_on_hand * products.unit_cost), 0) as val')
            ->value('val');

        return view('reports.valuation', compact('records', 'warehouses', 'categories', 'warehouseId', 'categoryId', 'totalValuation'));
    }

    public function movements(Request $request)
    {
        $dateFrom = $request->query('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());
        $type = $request->query('type');

        $query = StockMovement::with(['product', 'fromLocation', 'toLocation', 'user'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if ($type) {
            $query->where('reference_type', $type);
        }

        $movements = $query->latest()->paginate(25)->withQueryString();

        return view('reports.movements', compact('movements', 'dateFrom', 'dateTo', 'type'));
    }

    public function utilization()
    {
        $warehouses = Warehouse::with(['zones.storageLocations.stocks'])->get();

        return view('reports.utilization', compact('warehouses'));
    }
}
