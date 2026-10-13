@extends('layouts.app')

@section('title', 'New Sales Order')

@section('content')
<div class="mb-4">
    <a href="{{ route('outbound.index') }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to Outbound Orders
    </a>
    <h4 class="fw-bold mt-2">Create Customer Outbound Sales Order (SO)</h4>
</div>

<div class="card card-wms" style="max-width: 950px;">
    <div class="card-body p-4">
        <form action="{{ route('outbound.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="customer_id" class="form-label fs-13 fw-semibold">Client / Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select" required>
                        <option value="">-- Select Customer --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="warehouse_id" class="form-label fs-13 fw-semibold">Fulfillment Facility <span class="text-danger">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                        <option value="">-- Fulfilling Hub --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="priority" class="form-label fs-13 fw-semibold">Fulfillment Priority</label>
                    <select name="priority" id="priority" class="form-select" required>
                        <option value="low">Low Priority</option>
                        <option value="medium" selected>Standard (Medium)</option>
                        <option value="high">High Priority</option>
                        <option value="urgent">Urgent / Hot Shipment</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="shipping_carrier" class="form-label fs-13 fw-semibold">Logistics Carrier</label>
                    <input type="text" name="shipping_carrier" id="shipping_carrier" class="form-control" placeholder="e.g. DHL Express Global / FedEx Freight" value="{{ old('shipping_carrier') }}">
                </div>
                <div class="col-md-6">
                    <label for="scheduled_ship_date" class="form-label fs-13 fw-semibold">Scheduled Dispatch Date</label>
                    <input type="date" name="scheduled_ship_date" id="scheduled_ship_date" class="form-control" value="{{ old('scheduled_ship_date') }}">
                </div>
            </div>

            <div class="mb-4">
                <label for="notes" class="form-label fs-13 fw-semibold">Special Packing &amp; Dispatch Instructions</label>
                <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Packaging constraints, delivery dock notice...">{{ old('notes') }}</textarea>
            </div>

            <!-- Dynamic Line Items -->
            <div class="card border mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold fs-13">Order Line Items</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addItemRow('items-container', 'item-template')">
                        <i class="bi bi-plus-lg me-1"></i> Add Item
                    </button>
                </div>
                <div class="card-body p-3">
                    <div id="items-container">
                        <div class="row g-2 align-items-center mb-2 dynamic-item-row">
                            <div class="col-md-5">
                                <label class="fs-12 text-muted">Product SKU</label>
                                <select name="items[0][product_id]" class="form-select form-select-sm" required onchange="populatePrice(this)">
                                    <option value="">-- Select Product --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-price="{{ $p->unit_price }}">{{ $p->name }} [{{ $p->sku }}]</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="fs-12 text-muted">Quantity Ordered</label>
                                <input type="number" step="0.0001" min="0.01" name="items[0][quantity_ordered]" class="form-control form-control-sm item-qty" value="5" required oninput="recalculateOrderTotal()">
                            </div>
                            <div class="col-md-3">
                                <label class="fs-12 text-muted">Unit Sales Price ($)</label>
                                <input type="number" step="0.0001" min="0" name="items[0][unit_price]" class="form-control form-control-sm item-price" value="0.00" required oninput="recalculateOrderTotal()">
                            </div>
                            <div class="col-md-1 text-center pt-3">
                                <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeItemRow(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end align-items-center mt-3 pt-2 border-top">
                        <span class="fs-13 text-muted me-2">Grand Total Amount:</span>
                        <span id="grand-total" class="fs-5 fw-bold text-success">$0.00</span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('outbound.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Create Sales Order</button>
            </div>
        </form>
    </div>
</div>

<template id="item-template">
    <div class="row g-2 align-items-center mb-2 dynamic-item-row">
        <div class="col-md-5">
            <select name="items[{index}][product_id]" class="form-select form-select-sm" required onchange="populatePrice(this)">
                <option value="">-- Select Product --</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" data-price="{{ $p->unit_price }}">{{ $p->name }} [{{ $p->sku }}]</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" step="0.0001" min="0.01" name="items[{index}][quantity_ordered]" class="form-control form-control-sm item-qty" value="5" required oninput="recalculateOrderTotal()">
        </div>
        <div class="col-md-3">
            <input type="number" step="0.0001" min="0" name="items[{index}][unit_price]" class="form-control form-control-sm item-price" value="0.00" required oninput="recalculateOrderTotal()">
        </div>
        <div class="col-md-1 text-center">
            <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeItemRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>

<script>
function populatePrice(select) {
    const selected = select.options[select.selectedIndex];
    const price = selected.getAttribute('data-price') || 0;
    const row = select.closest('.dynamic-item-row');
    const priceInput = row.querySelector('.item-price');
    if (priceInput) {
        priceInput.value = parseFloat(price).toFixed(2);
        recalculateOrderTotal();
    }
}
</script>
@endsection
