@extends('layouts.app')

@section('title', 'Edit ' . $product->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('products.show', $product) }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to {{ $product->name }}
    </a>
    <h4 class="fw-bold mt-2">Edit Product SKU</h4>
</div>

<div class="card card-wms" style="max-width: 850px;">
    <div class="card-body p-4">
        <form action="{{ route('products.update', $product) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="sku" class="form-label fs-13 fw-semibold">SKU</label>
                    <input type="text" name="sku" id="sku" class="form-control text-uppercase font-monospace" value="{{ old('sku', $product->sku) }}" required>
                </div>
                <div class="col-md-6">
                    <label for="barcode" class="form-label fs-13 fw-semibold">Barcode / EAN / UPC</label>
                    <input type="text" name="barcode" id="barcode" class="form-control font-monospace" value="{{ old('barcode', $product->barcode) }}">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label for="name" class="form-label fs-13 fw-semibold">Product Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="category_id" class="form-label fs-13 fw-semibold">Category</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="uom" class="form-label fs-13 fw-semibold">Unit of Measure</label>
                    <select name="uom" id="uom" class="form-select" required>
                        <option value="PCS" {{ old('uom', $product->uom) === 'PCS' ? 'selected' : '' }}>Pieces (PCS)</option>
                        <option value="BOX" {{ old('uom', $product->uom) === 'BOX' ? 'selected' : '' }}>Box / Carton</option>
                        <option value="PALLET" {{ old('uom', $product->uom) === 'PALLET' ? 'selected' : '' }}>Pallet</option>
                        <option value="KG" {{ old('uom', $product->uom) === 'KG' ? 'selected' : '' }}>Kilograms (KG)</option>
                        <option value="LTR" {{ old('uom', $product->uom) === 'LTR' ? 'selected' : '' }}>Liters (LTR)</option>
                        <option value="METER" {{ old('uom', $product->uom) === 'METER' ? 'selected' : '' }}>Meters (METER)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="unit_cost" class="form-label fs-13 fw-semibold">Unit Cost ($)</label>
                    <input type="number" step="0.0001" name="unit_cost" id="unit_cost" class="form-control" value="{{ old('unit_cost', $product->unit_cost) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="unit_price" class="form-label fs-13 fw-semibold">MSRP / Price ($)</label>
                    <input type="number" step="0.0001" name="unit_price" id="unit_price" class="form-control" value="{{ old('unit_price', $product->unit_price) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="weight_kg" class="form-label fs-13 fw-semibold">Weight (KG)</label>
                    <input type="number" step="0.001" name="weight_kg" id="weight_kg" class="form-control" value="{{ old('weight_kg', $product->weight_kg) }}" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="volume_cbm" class="form-label fs-13 fw-semibold">Volume (CBM)</label>
                    <input type="number" step="0.0001" name="volume_cbm" id="volume_cbm" class="form-control" value="{{ old('volume_cbm', $product->volume_cbm) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="min_stock_level" class="form-label fs-13 fw-semibold">Safety Stock Min</label>
                    <input type="number" step="1" name="min_stock_level" id="min_stock_level" class="form-control" value="{{ old('min_stock_level', $product->min_stock_level) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="reorder_point" class="form-label fs-13 fw-semibold">Reorder Point</label>
                    <input type="number" step="1" name="reorder_point" id="reorder_point" class="form-control" value="{{ old('reorder_point', $product->reorder_point) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="max_stock_level" class="form-label fs-13 fw-semibold">Max Stock Level</label>
                    <input type="number" step="1" name="max_stock_level" id="max_stock_level" class="form-control" value="{{ old('max_stock_level', $product->max_stock_level) }}" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="description" class="form-label fs-13 fw-semibold">Product Description</label>
                <textarea name="description" id="description" rows="3" class="form-control">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Update Product</button>
            </div>
        </form>
    </div>
</div>
@endsection
