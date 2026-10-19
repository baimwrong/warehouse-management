<?php

namespace App\Http\Controllers;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Exception;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = StockAdjustment::with(['warehouse', 'requester', 'approver', 'items.product']);

        if ($status) {
            $query->where('status', $status);
        }

        $adjustments = $query->latest()->paginate(15)->withQueryString();

        return view('adjustments.index', compact('adjustments', 'status'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('status', 'active')->get();
        $products = Product::where('is_active', true)->with('batches')->orderBy('name')->get();
        $locations = StorageLocation::where('is_active', true)->orderBy('location_code')->get();

        return view('adjustments.create', compact('warehouses', 'products', 'locations'));
    }

    public function store(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'reason' => 'required|string|in:cycle_count,damaged,expired,write_off,variance,return',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.storage_location_id' => [
                'required',
                Rule::exists('storage_locations', 'id')->where('warehouse_id', $warehouseId),
            ],
            'items.*.product_batch_id' => 'nullable|exists:product_batches,id',
            'items.*.new_quantity' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $adjustmentNumber = 'ADJ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $adjustment = StockAdjustment::create([
                'adjustment_number' => $adjustmentNumber,
                'warehouse_id' => $validated['warehouse_id'],
                'reason' => $validated['reason'],
                'status' => 'pending',
                'requested_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $batchId = !empty($itemData['product_batch_id']) ? $itemData['product_batch_id'] : null;

                $stock = InventoryStock::where('warehouse_id', $validated['warehouse_id'])
                    ->where('storage_location_id', $itemData['storage_location_id'])
                    ->where('product_id', $itemData['product_id'])
                    ->where('product_batch_id', $batchId)
                    ->first();

                $previousQty = $stock ? (float) $stock->quantity_on_hand : 0.0;
                $newQty = (float) $itemData['new_quantity'];
                $diffQty = $newQty - $previousQty;

                $adjustment->items()->create([
                    'product_id' => $itemData['product_id'],
                    'storage_location_id' => $itemData['storage_location_id'],
                    'product_batch_id' => $batchId,
                    'previous_quantity' => $previousQty,
                    'new_quantity' => $newQty,
                    'difference_quantity' => $diffQty,
                ]);
            }
        });

        return redirect()->route('adjustments.index')
            ->with('success', 'Stock adjustment request submitted and waiting for manager approval.');
    }

    public function show(StockAdjustment $adjustment)
    {
        $adjustment->load(['warehouse', 'requester', 'approver', 'items.product', 'items.location']);
        return view('adjustments.show', compact('adjustment'));
    }

    public function approve(StockAdjustment $adjustment)
    {
        if (!auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Only warehouse managers and admins can approve stock adjustments.');
        }

        if ($adjustment->status !== 'pending') {
            return back()->withErrors(['error' => 'Adjustment is already processed.']);
        }

        // Enforce segregation of duties: Requester cannot approve their own adjustment unless super admin
        if ($adjustment->requested_by === auth()->id() && !auth()->user()->isAdmin()) {
            return back()->withErrors(['error' => 'Segregation of duties violation: You cannot approve your own stock adjustment request. Another manager must review and approve it.']);
        }

        try {
            DB::transaction(function () use ($adjustment) {
                foreach ($adjustment->items as $item) {
                    // Lock current inventory row to get live, up-to-the-minute balance
                    $stock = InventoryStock::where('warehouse_id', $adjustment->warehouse_id)
                        ->where('storage_location_id', $item->storage_location_id)
                        ->where('product_id', $item->product_id)
                        ->where('product_batch_id', $item->product_batch_id)
                        ->lockForUpdate()
                        ->first();

                    $currentOnHand = $stock ? (float) $stock->quantity_on_hand : 0.0;
                    $reconciledDiff = (float) $item->new_quantity - $currentOnHand;

                    if ($reconciledDiff > 0) {
                        $this->inventoryService->increaseStock(
                            warehouseId: $adjustment->warehouse_id,
                            locationId: $item->storage_location_id,
                            productId: $item->product_id,
                            quantity: $reconciledDiff,
                            batchId: $item->product_batch_id,
                            referenceType: 'adjustment',
                            referenceId: $adjustment->id,
                            unitCost: (float) ($item->product->unit_cost ?? 0),
                            userId: auth()->id(),
                            notes: "Adjustment {$adjustment->adjustment_number} (+{$reconciledDiff})"
                        );
                    } elseif ($reconciledDiff < 0) {
                        $this->inventoryService->decreaseStock(
                            warehouseId: $adjustment->warehouse_id,
                            locationId: $item->storage_location_id,
                            productId: $item->product_id,
                            quantity: abs($reconciledDiff),
                            batchId: $item->product_batch_id,
                            referenceType: 'adjustment',
                            referenceId: $adjustment->id,
                            unitCost: (float) ($item->product->unit_cost ?? 0),
                            userId: auth()->id(),
                            notes: "Adjustment {$adjustment->adjustment_number} ({$reconciledDiff})"
                        );
                    }

                    // Update item record with live reconciled previous and difference values
                    $item->update([
                        'previous_quantity' => $currentOnHand,
                        'difference_quantity' => $reconciledDiff,
                    ]);
                }

                $adjustment->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => Carbon::now(),
                ]);
            });

            return redirect()->route('adjustments.show', $adjustment)
                ->with('success', "Stock Adjustment '{$adjustment->adjustment_number}' approved and ledger reconciled.");
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Failed to approve adjustment: ' . $e->getMessage()]);
        }
    }

    public function reject(StockAdjustment $adjustment)
    {
        if (!auth()->user()->isManager()) {
            abort(403, 'Unauthorized. Only warehouse managers and admins can reject stock adjustments.');
        }

        if ($adjustment->status !== 'pending') {
            return back()->withErrors(['error' => 'Adjustment is already processed.']);
        }

        $adjustment->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => Carbon::now(),
        ]);

        return redirect()->route('adjustments.show', $adjustment)
            ->with('info', "Stock Adjustment '{$adjustment->adjustment_number}' has been rejected.");
    }
}
