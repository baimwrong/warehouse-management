@extends('layouts.app')

@section('title', 'Products Catalog')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Products Master Catalog</h4>
        <p class="text-muted fs-14 mb-0">Master catalog of SKUs, categories, safety thresholds, and unit costs</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('products.barcodes') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-upc-scan me-1"></i> Print Barcode Labels
        </a>
        <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Product SKU
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card card-wms mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('products.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search product name, SKU, or barcode..." value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">-- All Categories --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Product / SKU</th>
                    <th>Category</th>
                    <th>Unit Cost / Price</th>
                    <th class="text-end">On-Hand Stock</th>
                    <th class="text-end">Thresholds</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $prod)
                <tr>
                    <td>
                        <a href="{{ route('products.show', $prod) }}" class="fw-bold text-dark text-decoration-none">
                            {{ $prod->name }}
                        </a>
                        <div class="d-flex align-items-center gap-2 text-muted fs-12 mt-0.5">
                            <span class="font-monospace text-primary fw-semibold">{{ $prod->sku }}</span>
                            @if($prod->barcode)
                                <span>&bull; <i class="bi bi-upc me-0.5"></i>{{ $prod->barcode }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-soft-primary">{{ $prod->category->name ?? 'General' }}</span>
                    </td>
                    <td>
                        <div class="fs-13 fw-semibold">${{ number_format($prod->unit_cost, 2) }} cost</div>
                        <div class="text-muted fs-11">${{ number_format($prod->unit_price, 2) }} msrp</div>
                    </td>
                    <td class="text-end">
                        <div class="fw-bold fs-14 {{ $prod->total_quantity_on_hand <= $prod->min_stock_level ? 'text-danger' : 'text-dark' }}">
                            {{ number_format($prod->total_quantity_on_hand) }} {{ $prod->uom }}
                        </div>
                        <div class="text-muted fs-11">Valuation: ${{ number_format($prod->total_valuation, 2) }}</div>
                    </td>
                    <td class="text-end fs-12 text-muted">
                        <div>Min: {{ number_format($prod->min_stock_level) }}</div>
                        <div>Reorder: {{ number_format($prod->reorder_point) }}</div>
                    </td>
                    <td>
                        @if($prod->stock_status === 'out_of_stock')
                            <span class="badge badge-soft-danger">Out of Stock</span>
                        @elseif($prod->stock_status === 'critical_low')
                            <span class="badge badge-soft-danger">Critical Low</span>
                        @elseif($prod->stock_status === 'reorder_needed')
                            <span class="badge badge-soft-warning">Reorder</span>
                        @else
                            <span class="badge badge-soft-success">In Stock</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('products.show', $prod) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('products.edit', $prod) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No products found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $products->links() }}
    </div>
    @endif
</div>
@endsection
