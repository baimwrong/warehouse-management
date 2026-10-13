@extends('layouts.app')

@section('title', 'Add Product SKU')

@section('content')
<div class="mb-4">
    <a href="{{ route('products.index') }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to Products
    </a>
    <h4 class="fw-bold mt-2">Create New Product SKU</h4>
</div>

<div class="card card-wms" style="max-width: 850px;">
    <div class="card-body p-4">
        <form action="{{ route('products.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="sku" class="form-label fs-13 fw-semibold">SKU (Stock Keeping Unit) <span class="text-danger">*</span></label>
                    <input type="text" name="sku" id="sku" class="form-control text-uppercase font-monospace" placeholder="e.g. ELEC-SENS-001" value="{{ old('sku') }}" required>
                </div>
                <div class="col-md-6">
                    <label for="barcode" class="form-label fs-13 fw-semibold">Barcode / EAN / UPC</label>
                    <input type="text" name="barcode" id="barcode" class="form-control font-monospace" placeholder="e.g. 893521094101" value="{{ old('barcode') }}">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label for="name" class="form-label fs-13 fw-semibold">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. High Precision Ultrasonic Sensor" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-4">
                    <label for="category_id" class="form-label fs-13 fw-semibold">Category</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="uom" class="form-label fs-13 fw-semibold">Unit of Measure <span class="text-danger">*</span></label>
                    <select name="uom" id="uom" class="form-select" required>
                        <option value="PCS" {{ old('uom') === 'PCS' ? 'selected' : '' }}>Pieces (PCS)</option>
                        <option value="BOX" {{ old('uom') === 'BOX' ? 'selected' : '' }}>Box / Carton</option>
                        <option value="PALLET" {{ old('uom') === 'PALLET' ? 'selected' : '' }}>Pallet</option>
                        <option value="KG" {{ old('uom') === 'KG' ? 'selected' : '' }}>Kilograms (KG)</option>
                        <option value="LTR" {{ old('uom') === 'LTR' ? 'selected' : '' }}>Liters (LTR)</option>
                        <option value="METER" {{ old('uom') === 'METER' ? 'selected' : '' }}>Meters (METER)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="unit_cost" class="form-label fs-13 fw-semibold">Unit Cost ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" name="unit_cost" id="unit_cost" class="form-control" value="{{ old('unit_cost', 0) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="unit_price" class="form-label fs-13 fw-semibold">MSRP / Price ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" name="unit_price" id="unit_price" class="form-control" value="{{ old('unit_price', 0) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="weight_kg" class="form-label fs-13 fw-semibold">Weight (KG)</label>
                    <input type="number" step="0.001" name="weight_kg" id="weight_kg" class="form-control" value="{{ old('weight_kg', 0) }}" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="volume_cbm" class="form-label fs-13 fw-semibold">Volume (CBM)</label>
                    <input type="number" step="0.0001" name="volume_cbm" id="volume_cbm" class="form-control" value="{{ old('volume_cbm', 0) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="min_stock_level" class="form-label fs-13 fw-semibold">Safety Stock Min</label>
                    <input type="number" step="1" name="min_stock_level" id="min_stock_level" class="form-control" value="{{ old('min_stock_level', 10) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="reorder_point" class="form-label fs-13 fw-semibold">Reorder Point</label>
                    <input type="number" step="1" name="reorder_point" id="reorder_point" class="form-control" value="{{ old('reorder_point', 25) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="max_stock_level" class="form-label fs-13 fw-semibold">Max Stock Level</label>
                    <input type="number" step="1" name="max_stock_level" id="max_stock_level" class="form-control" value="{{ old('max_stock_level', 200) }}" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="description" class="form-label fs-13 fw-semibold">Product Description / Technical Specs</label>
                <textarea name="description" id="description" rows="3" class="form-control" placeholder="Detailed product specifications...">{{ old('description') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Create Product</button>
            </div>
        </form>
    </div>
</div>
@endsection
