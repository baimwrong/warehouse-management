<?php

namespace App\Http\Controllers;

use App\Models\InboundOrder;
use App\Models\InventoryStock;
use App\Models\OutboundOrder;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');

        $warehouses = Warehouse::where('status', 'active')->get();

        // Query stock stats
        $stockQuery = InventoryStock::query();
        $locationQuery = StorageLocation::where('is_active', true);
        $inboundQuery = InboundOrder::whereIn('status', ['pending_receipt', 'partially_received']);
        $outboundQuery = OutboundOrder::whereIn('status', ['confirmed', 'picking', 'packed']);

        if ($warehouseId) {
            $stockQuery->where('warehouse_id', $warehouseId);
            $locationQuery->where('warehouse_id', $warehouseId);
            $inboundQuery->where('warehouse_id', $warehouseId);
            $outboundQuery->where('warehouse_id', $warehouseId);
        }

        $totalSkus = Product::where('is_active', true)->count();
        $totalItemsOnHand = (float) $stockQuery->sum('quantity_on_hand');

        // Total inventory valuation
        $totalValuation = (float) DB::table('inventory_stocks')
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->when($warehouseId, fn($q) => $q->where('inventory_stocks.warehouse_id', $warehouseId))
            ->selectRaw('COALESCE(SUM(inventory_stocks.quantity_on_hand * products.unit_cost), 0) as total_val')
            ->value('total_val');

        // Bins & Utilization
        $totalBins = $locationQuery->count();
        $occupiedBins = (clone $locationQuery)->where('is_occupied', true)->count();
        $binUtilization = $totalBins > 0 ? round(($occupiedBins / $totalBins) * 100, 1) : 0;

        // Pending counts
        $pendingInbound = $inboundQuery->count();
        $pendingOutbound = $outboundQuery->count();

        // Low stock products
        $lowStockProducts = Product::where('is_active', true)
            ->with(['stocks', 'category'])
            ->get()
            ->filter(fn($p) => $p->total_quantity_on_hand <= $p->reorder_point)
            ->take(8);

        // Recent audit movements
        $recentMovements = StockMovement::with(['product', 'fromLocation', 'toLocation', 'user'])
            ->latest()
            ->take(8)
            ->get();

        // 7-day movement trend for Chart.js
        $trendDates = [];
        $inboundCounts = [];
        $outboundCounts = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $trendDates[] = Carbon::now()->subDays($i)->format('M d');

            $inboundCounts[] = (float) StockMovement::whereDate('created_at', $date)
                ->whereIn('reference_type', ['inbound', 'initial', 'bulk_import'])
                ->sum('quantity');

            $outboundCounts[] = (float) StockMovement::whereDate('created_at', $date)
                ->where('reference_type', 'outbound')
                ->sum('quantity');
        }

        return view('dashboard.index', compact(
            'warehouses',
            'warehouseId',
            'totalSkus',
            'totalItemsOnHand',
            'totalValuation',
            'totalBins',
            'occupiedBins',
            'binUtilization',
            'pendingInbound',
            'pendingOutbound',
            'lowStockProducts',
            'recentMovements',
            'trendDates',
            'inboundCounts',
            'outboundCounts'
        ));
    }
}
