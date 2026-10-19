<?php

namespace App\Http\Controllers;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Exception;

class StockMovementController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request)
    {
        $type = $request->query('type');
        $productId = $request->query('product_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = StockMovement::with([
            'product',
            'batch',
            'fromLocation.warehouse',
            'toLocation.warehouse',
            'user',
        ]);

        if ($type) {
            $query->where('reference_type', $type);
        }
        if ($productId) {
            $query->where('product_id', $productId);
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $movements = $query->latest()->paginate(25)->withQueryString();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('movements.index', compact('movements', 'products', 'type', 'productId', 'dateFrom', 'dateTo'));
    }

    public function transferForm()
    {
        $warehouses = Warehouse::where('status', 'active')->with(['zones.storageLocations'])->get();
        $products = Product::where('is_active', true)->with('batches')->orderBy('name')->get();

        return view('movements.transfer', compact('warehouses', 'products'));
    }

    public function processTransfer(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'from_location_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('storage_locations', 'id')->where('warehouse_id', $warehouseId),
            ],
            'to_location_id' => [
                'required',
                'different:from_location_id',
                \Illuminate\Validation\Rule::exists('storage_locations', 'id')->where('warehouse_id', $warehouseId),
            ],
            'quantity' => 'required|numeric|min:0.0001',
            'product_batch_id' => 'nullable|exists:product_batches,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->inventoryService->transferStock(
                warehouseId: (int) $validated['warehouse_id'],
                fromLocationId: (int) $validated['from_location_id'],
                toLocationId: (int) $validated['to_location_id'],
                productId: (int) $validated['product_id'],
                quantity: (float) $validated['quantity'],
                batchId: !empty($validated['product_batch_id']) ? (int) $validated['product_batch_id'] : null,
                userId: auth()->id(),
                notes: $validated['notes'] ?? 'Manual internal bin transfer'
            );

            return redirect()->route('movements.index')
                ->with('success', "Stock transfer completed successfully.");
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
