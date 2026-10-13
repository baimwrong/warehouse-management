@extends('layouts.app')

@section('title', 'New Stock Adjustment')

@section('content')
<div class="mb-4">
    <a href="{{ route('adjustments.index') }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to Adjustments
    </a>
    <h4 class="fw-bold mt-2">Request Stock Adjustment / Cycle Count</h4>
</div>

<div class="card card-wms" style="max-width: 900px;">
    <div class="card-body p-4">
        <form action="{{ route('adjustments.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="warehouse_id" class="form-label fs-13 fw-semibold">Warehouse Facility <span class="text-danger">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                        <option value="">-- Select Facility --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="reason" class="form-label fs-13 fw-semibold">Reason for Adjustment <span class="text-danger">*</span></label>
                    <select name="reason" id="reason" class="form-select" required>
                        <option value="cycle_count">Routine Cycle Count Discrepancy</option>
                        <option value="damaged">Damaged in Handling</option>
                        <option value="expired">Expired Beyond Shelf-Life</option>
                        <option value="write_off">Inventory Write-Off</option>
                        <option value="variance">Physical Audit Variance</option>
                        <option value="return">Customer Return Inspection</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label for="notes" class="form-label fs-13 fw-semibold">Detailed Justification / Notes</label>
                <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Provide context for manager review...">{{ old('notes') }}</textarea>
            </div>

            <!-- Dynamic Line Items -->
            <div class="card border mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold fs-13">Adjustment Line Items</span>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addItemRow('items-container', 'item-template')">
                        <i class="bi bi-plus-lg me-1"></i> Add Item
                    </button>
                </div>
                <div class="card-body p-3">
                    <div id="items-container">
                        <!-- Initial Row -->
                        <div class="row g-2 align-items-center mb-2 dynamic-item-row">
                            <div class="col-md-5">
                                <label class="fs-12 text-muted">Product SKU</label>
                                <select name="items[0][product_id]" class="form-select form-select-sm" required>
                                    <option value="">-- Select Product --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} [{{ $p->sku }}]</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="fs-12 text-muted">Bin Location</label>
                                <select name="items[0][storage_location_id]" class="form-select form-select-sm font-monospace" required>
                                    <option value="">-- Storage Bin --</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->location_code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="fs-12 text-muted">Actual Count</label>
                                <input type="number" step="0.0001" min="0" name="items[0][new_quantity]" class="form-control form-control-sm" placeholder="Count" required>
                            </div>
                            <div class="col-md-1 text-center pt-3">
                                <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeItemRow(this)" title="Remove">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('adjustments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Submit for Approval</button>
            </div>
        </form>
    </div>
</div>

<!-- Template for adding new rows -->
<template id="item-template">
    <div class="row g-2 align-items-center mb-2 dynamic-item-row">
        <div class="col-md-5">
            <select name="items[{index}][product_id]" class="form-select form-select-sm" required>
                <option value="">-- Select Product --</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} [{{ $p->sku }}]</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <select name="items[{index}][storage_location_id]" class="form-select form-select-sm font-monospace" required>
                <option value="">-- Storage Bin --</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->location_code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" step="0.0001" min="0" name="items[{index}][new_quantity]" class="form-control form-control-sm" placeholder="Count" required>
        </div>
        <div class="col-md-1 text-center">
            <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeItemRow(this)" title="Remove">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>
@endsection
