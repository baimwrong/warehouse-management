<?php

namespace App\Http\Controllers;

use App\Models\InboundOrder;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\PutawayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class InboundOrderController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected PutawayService $putawayService
    ) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = InboundOrder::with(['supplier', 'warehouse', 'creator', 'items.product']);

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        return view('inbound.index', compact('orders', 'status'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('inbound.create', compact('suppliers', 'warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_expected' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $orderNumber = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $totalAmount = 0.0;

            foreach ($validated['items'] as $item) {
                $totalAmount += ($item['quantity_expected'] * $item['unit_cost']);
            }

            $order = InboundOrder::create([
                'order_number' => $orderNumber,
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'status' => 'pending_receipt',
                'expected_date' => $validated['expected_date'] ?? null,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_expected' => $item['quantity_expected'],
                    'unit_cost' => $item['unit_cost'],
                    'quantity_received' => 0.0,
                    'status' => 'pending',
                ]);
            }

            return $order;
        });

        return redirect()->route('inbound.show', $order)
            ->with('success', "Inbound Order '{$order->order_number}' created successfully.");
    }

    public function show(InboundOrder $order)
    {
        $order->load(['supplier', 'warehouse', 'creator', 'items.product', 'items.targetLocation']);

        return view('inbound.show', compact('order'));
    }

    public function receiveForm(InboundOrder $order)
    {
        if ($order->status === 'received' || $order->status === 'cancelled') {
            return redirect()->route('inbound.show', $order)->withErrors(['error' => 'This order is already completed or cancelled.']);
        }

        $order->load(['supplier', 'warehouse', 'items.product']);
        $locations = StorageLocation::where('warehouse_id', $order->warehouse_id)
            ->where('is_active', true)
            ->orderBy('location_code')
            ->get();

        // Generate putaway recommendations for each line item
        $recommendations = [];
        foreach ($order->items as $item) {
            $recommendations[$item->id] = $this->putawayService->suggestBins(
                $order->warehouse_id,
                $item->product_id,
                $item->quantity_expected - $item->quantity_received
            );
        }

        return view('inbound.receive', compact('order', 'locations', 'recommendations'));
    }

    public function processReceipt(Request $request, InboundOrder $order)
    {
        if ($order->status === 'received' || $order->status === 'cancelled') {
            return redirect()->route('inbound.show', $order)
                ->withErrors(['error' => 'This purchase order is already completed or cancelled.']);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inbound_order_items,id',
            'items.*.received_qty' => 'required|numeric|min:0',
            'items.*.target_location_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('storage_locations', 'id')->where('warehouse_id', $order->warehouse_id),
            ],
            'items.*.batch_number' => 'nullable|string|max:80',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($validated, $order) {
            $allReceived = true;

            foreach ($validated['items'] as $itemData) {
                $orderItem = $order->items()->findOrFail($itemData['item_id']);
                $qty = (float) $itemData['received_qty'];

                if ($qty > 0) {
                    $batchId = null;
                    if (!empty($itemData['batch_number'])) {
                        $batch = $orderItem->product->batches()->firstOrCreate(
                            [
                                'batch_number' => trim($itemData['batch_number']),
                            ],
                            [
                                'expiry_date' => $itemData['expiry_date'] ?? null,
                                'supplier_id' => $order->supplier_id,
                            ]
                        );
                        $batchId = $batch->id;
                    }

                    $orderItem->quantity_received += $qty;
                    $orderItem->target_location_id = $itemData['target_location_id'];
                    $orderItem->batch_number = $itemData['batch_number'] ?? null;
                    $orderItem->expiry_date = $itemData['expiry_date'] ?? null;
                    $orderItem->status = $orderItem->quantity_received >= $orderItem->quantity_expected ? 'received' : 'partial';
                    $orderItem->save();

                    // Increase warehouse stock in chosen bin
                    $this->inventoryService->increaseStock(
                        warehouseId: $order->warehouse_id,
                        locationId: $itemData['target_location_id'],
                        productId: $orderItem->product_id,
                        quantity: $qty,
                        batchId: $batchId,
                        referenceType: 'inbound',
                        referenceId: $order->id,
                        unitCost: (float) $orderItem->unit_cost,
                        userId: auth()->id(),
                        notes: "Received against PO {$order->order_number}"
                    );
                }

                if ($orderItem->quantity_received < $orderItem->quantity_expected) {
                    $allReceived = false;
                }
            }

            $order->status = $allReceived ? 'received' : 'partially_received';
            $order->received_date = Carbon::now();
            $order->save();
        });

        return redirect()->route('inbound.show', $order)
            ->with('success', "Goods received and successfully put away into inventory.");
    }
}
