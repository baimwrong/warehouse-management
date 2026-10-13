@extends('layouts.app')

@section('title', 'New Purchase Order')

@section('content')
<div class="mb-4">
    <a href="{{ route('inbound.index') }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to Inbound Orders
    </a>
    <h4 class="fw-bold mt-2">Create Inbound Purchase Order (PO)</h4>
</div>

<div class="card card-wms" style="max-width: 950px;">
    <div class="card-body p-4">
        <form action="{{ route('inbound.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="supplier_id" class="form-label fs-13 fw-semibold">Supplier Vendor <span class="text-danger">*</span></label>
                    <select name="supplier_id" id="supplier_id" class="form-select" required>
                        <option value="">-- Select Supplier --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>
                                {{ $sup->name }} ({{ $sup->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="warehouse_id" class="form-label fs-13 fw-semibold">Destination Facility <span class="text-danger">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                        <option value="">-- Receiving Hub --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="expected_date" class="form-label fs-13 fw-semibold">Expected Arrival Date</label>
                    <input type="date" name="expected_date" id="expected_date" class="form-control" value="{{ old('expected_date') }}">
                </div>
            </div>

            <div class="mb-4">
                <label for="notes" class="form-label fs-13 fw-semibold">Order Notes &amp; Delivery Instructions</label>
                <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Carrier tracking, bill of lading notes...">{{ old('notes') }}</textarea>
            </div>

            <!-- Dynamic Line Items -->
            <div class="card border mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold fs-13">PO Line Items</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addItemRow('items-container', 'item-template')">
                        <i class="bi bi-plus-lg me-1"></i> Add Item
                    </button>
                </div>
                <div class="card-body p-3">
                    <div id="items-container">
                        <div class="row g-2 align-items-center mb-2 dynamic-item-row">
                            <div class="col-md-5">
                                <label class="fs-12 text-muted">Product SKU</label>
                                <select name="items[0][product_id]" class="form-select form-select-sm" required onchange="populateCost(this)">
                                    <option value="">-- Select Product --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-cost="{{ $p->unit_cost }}">{{ $p->name }} [{{ $p->sku }}]</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="fs-12 text-muted">Quantity Expected</label>
                                <input type="number" step="0.0001" min="0.01" name="items[0][quantity_expected]" class="form-control form-control-sm item-qty" value="10" required oninput="recalculateOrderTotal()">
                            </div>
                            <div class="col-md-3">
                                <label class="fs-12 text-muted">Unit Cost ($)</label>
                                <input type="number" step="0.0001" min="0" name="items[0][unit_cost]" class="form-control form-control-sm item-price" value="0.00" required oninput="recalculateOrderTotal()">
                            </div>
                            <div class="col-md-1 text-center pt-3">
                                <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeItemRow(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end align-items-center mt-3 pt-2 border-top">
                        <span class="fs-13 text-muted me-2">Estimated PO Total:</span>
                        <span id="grand-total" class="fs-5 fw-bold text-success">$0.00</span>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('inbound.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Generate Purchase Order</button>
            </div>
        </form>
    </div>
</div>

<template id="item-template">
    <div class="row g-2 align-items-center mb-2 dynamic-item-row">
        <div class="col-md-5">
            <select name="items[{index}][product_id]" class="form-select form-select-sm" required onchange="populateCost(this)">
                <option value="">-- Select Product --</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" data-cost="{{ $p->unit_cost }}">{{ $p->name }} [{{ $p->sku }}]</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" step="0.0001" min="0.01" name="items[{index}][quantity_expected]" class="form-control form-control-sm item-qty" value="10" required oninput="recalculateOrderTotal()">
        </div>
        <div class="col-md-3">
            <input type="number" step="0.0001" min="0" name="items[{index}][unit_cost]" class="form-control form-control-sm item-price" value="0.00" required oninput="recalculateOrderTotal()">
        </div>
        <div class="col-md-1 text-center">
            <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeItemRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>

<script>
function populateCost(select) {
    const selected = select.options[select.selectedIndex];
    const cost = selected.getAttribute('data-cost') || 0;
    const row = select.closest('.dynamic-item-row');
    const priceInput = row.querySelector('.item-price');
    if (priceInput) {
        priceInput.value = parseFloat(cost).toFixed(2);
        recalculateOrderTotal();
    }
}
</script>
@endsection
