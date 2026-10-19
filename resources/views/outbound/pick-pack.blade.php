@extends('layouts.app')

@section('title', 'Pick & Pack ' . $order->order_number)

@section('content')
<div class="mb-4">
    <a href="{{ route('outbound.show', $order) }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to {{ $order->order_number }}
    </a>
    <h4 class="fw-bold mt-2"><i class="bi bi-box text-primary me-2"></i> Pick, Pack &amp; Dispatch Fulfillment</h4>
    <p class="text-muted fs-14">Select inventory source bins, verify picking quantities, and record carrier tracking</p>
</div>

<form action="{{ route('outbound.pick-pack.process', $order) }}" method="POST">
    @csrf

    <div class="card card-wms mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Pick List Items</span>
            <span class="badge bg-primary">Facility: {{ $order->warehouse->name }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-wms align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 280px;">Product SKU</th>
                            <th class="text-end" style="width: 120px;">Ordered</th>
                            <th class="text-end" style="width: 140px;">Quantity to Pick</th>
                            <th>Select Inventory Source Bin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $index => $item)
                        @php
                            $availableStocks = $pickLocations[$item->id] ?? collect();
                        @endphp
                        <tr>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                                <div class="fw-bold">{{ $item->product->name }}</div>
                                <div class="font-monospace text-primary fs-12">{{ $item->product->sku }}</div>
                            </td>
                            <td class="text-end fw-semibold">
                                {{ number_format($item->quantity_ordered) }} {{ $item->product->uom }}
                            </td>
                            <td class="text-end">
                                <input type="number" step="0.0001" min="0" max="{{ $item->quantity_ordered }}" 
                                       name="items[{{ $index }}][picked_qty]" 
                                       class="form-control form-control-sm text-end fw-bold" 
                                       value="{{ $item->quantity_ordered }}" required>
                            </td>
                            <td>
                                <select name="items[{{ $index }}][source_location_id]" class="form-select form-select-sm font-monospace" required>
                                    <option value="">-- Choose Source Bin with Stock --</option>
                                    @foreach($availableStocks as $stk)
                                        <option value="{{ $stk->storage_location_id }}:{{ $stk->product_batch_id ?? '' }}" {{ ($item->source_location_id == $stk->storage_location_id && $item->product_batch_id == $stk->product_batch_id) ? 'selected' : '' }}>
                                            {{ $stk->location->location_code }} @if($stk->batch)[Lot: {{ $stk->batch->batch_number }}]@endif (Avail: {{ number_format($stk->available_quantity) }} {{ $item->product->uom }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Carrier & Dispatch Metadata Card -->
    <div class="card card-wms mb-4 p-3">
        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <label for="tracking_number" class="form-label fs-13 fw-semibold">Carrier Tracking Number / Waybill</label>
                <input type="text" name="tracking_number" id="tracking_number" class="form-control font-monospace" placeholder="e.g. 1Z9999999999999999" value="{{ old('tracking_number', $order->tracking_number) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label fs-13 fw-semibold">Fulfillment Action</label>
                <div class="d-flex gap-2">
                    <button type="submit" name="action" value="pick" class="btn btn-outline-primary flex-grow-1">
                        <i class="bi bi-box me-1"></i> Save as Packed
                    </button>
                    <button type="submit" name="action" value="ship" class="btn btn-success flex-grow-1" onclick="return confirm('Confirm order dispatch? Inventory stock will be deducted immediately.')">
                        <i class="bi bi-truck me-1"></i> Confirm Dispatch &amp; Deduct Stock
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
