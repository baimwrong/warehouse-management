<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\OutboundOrder;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class OutboundOrderController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $priority = $request->query('priority');

        $query = OutboundOrder::with(['customer', 'warehouse', 'creator', 'items.product']);

        if ($status) {
            $query->where('status', $status);
        }
        if ($priority) {
            $query->where('priority', $priority);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        return view('outbound.index', compact('orders', 'status', 'priority'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('outbound.create', compact('customers', 'warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'shipping_carrier' => 'nullable|string|max:100',
            'scheduled_ship_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $orderNumber = 'SO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $totalAmount = 0.0;

            foreach ($validated['items'] as $item) {
                $totalAmount += ($item['quantity_ordered'] * $item['unit_price']);
            }

            $order = OutboundOrder::create([
                'order_number' => $orderNumber,
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'status' => 'confirmed',
                'priority' => $validated['priority'],
                'shipping_carrier' => $validated['shipping_carrier'] ?? null,
                'scheduled_ship_date' => $validated['scheduled_ship_date'] ?? null,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_price' => $item['unit_price'],
                    'quantity_picked' => 0.0,
                    'quantity_packed' => 0.0,
                    'status' => 'pending',
                ]);
            }

            return $order;
        });

        return redirect()->route('outbound.show', $order)
            ->with('success', "Outbound Order '{$order->order_number}' created.");
    }

    public function show(OutboundOrder $order)
    {
        $order->load(['customer', 'warehouse', 'creator', 'items.product', 'items.sourceLocation']);

        return view('outbound.show', compact('order'));
    }

    public function pickPackForm(OutboundOrder $order)
    {
        if (in_array($order->status, ['shipped', 'delivered', 'cancelled'])) {
            return redirect()->route('outbound.show', $order)->withErrors(['error' => 'Order is already closed or shipped.']);
        }

        $order->load(['customer', 'warehouse', 'items.product']);

        // For each item, look up available storage locations with stock in this warehouse
        $pickLocations = [];
        foreach ($order->items as $item) {
            $pickLocations[$item->id] = InventoryStock::with(['location.zone', 'batch'])
                ->where('warehouse_id', $order->warehouse_id)
                ->where('product_id', $item->product_id)
                ->where('quantity_on_hand', '>', 0)
                ->get();
        }

        return view('outbound.pick-pack', compact('order', 'pickLocations'));
    }

    public function processPickPack(Request $request, OutboundOrder $order)
    {
        $validated = $request->validate([
            'tracking_number' => 'nullable|string|max:100',
            'action' => 'required|string|in:pick,ship',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:outbound_order_items,id',
            'items.*.source_location_id' => 'required|exists:storage_locations,id',
            'items.*.picked_qty' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated, $order) {
                $isShipping = $validated['action'] === 'ship';

                foreach ($validated['items'] as $itemData) {
                    $orderItem = $order->items()->findOrFail($itemData['item_id']);
                    $qty = (float) $itemData['picked_qty'];

                    $orderItem->source_location_id = $itemData['source_location_id'];
                    $orderItem->quantity_picked = $qty;
                    $orderItem->quantity_packed = $qty;
                    $orderItem->status = $isShipping ? 'shipped' : 'packed';
                    $orderItem->save();

                    if ($isShipping && $qty > 0) {
                        // Deduct physical inventory stock
                        $this->inventoryService->decreaseStock(
                            warehouseId: $order->warehouse_id,
                            locationId: $itemData['source_location_id'],
                            productId: $orderItem->product_id,
                            quantity: $qty,
                            batchId: null,
                            referenceType: 'outbound',
                            referenceId: $order->id,
                            unitCost: (float) $orderItem->product->unit_cost,
                            userId: auth()->id(),
                            notes: "Dispatched against Order {$order->order_number}"
                        );
                    }
                }

                if ($isShipping) {
                    $order->status = 'shipped';
                    $order->shipped_date = Carbon::now();
                    $order->tracking_number = $validated['tracking_number'] ?? $order->tracking_number;
                } else {
                    $order->status = 'packed';
                }
                $order->save();
            });

            $msg = $validated['action'] === 'ship'
                ? "Order '{$order->order_number}' marked as Shipped and inventory deducted."
                : "Order '{$order->order_number}' picked and packed. Ready for dispatch.";

            return redirect()->route('outbound.show', $order)->with('success', $msg);
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
