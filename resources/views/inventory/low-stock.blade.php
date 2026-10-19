@extends('layouts.app')

@section('title', 'Low Stock & Reorder Alerts')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <a href="{{ route('inventory.index') }}" class="text-decoration-none fs-13 text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to Inventory
        </a>
        <h4 class="fw-bold mt-2 mb-1"><i class="bi bi-exclamation-triangle text-danger me-2"></i> Low Stock &amp; Reorder Shortages</h4>
        <p class="text-muted fs-14 mb-0">Products whose on-hand inventory has breached safety minimums or reorder thresholds</p>
    </div>
    <a href="{{ route('inbound.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Create Restock Purchase Order
    </a>
</div>

<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Product / SKU</th>
                    <th>Category</th>
                    <th class="text-end">Current Stock</th>
                    <th class="text-end">Min Safety Level</th>
                    <th class="text-end">Reorder Point</th>
                    <th class="text-end">Suggested Reorder Qty</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $prod)
                @php
                    $deficit = max(0, $prod->max_stock_level - $prod->total_quantity_on_hand);
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('products.show', $prod) }}" class="fw-bold text-dark text-decoration-none">
                            {{ $prod->name }}
                        </a>
                        <div class="font-monospace text-primary fs-12">{{ $prod->sku }}</div>
                    </td>
                    <td>
                        <span class="badge badge-soft-secondary">{{ $prod->category->name ?? 'General' }}</span>
                    </td>
                    <td class="text-end fw-bold fs-14 {{ $prod->total_quantity_on_hand <= $prod->min_stock_level ? 'text-danger' : 'text-warning' }}">
                        {{ number_format($prod->total_quantity_on_hand) }} {{ $prod->uom }}
                    </td>
                    <td class="text-end text-muted">{{ number_format($prod->min_stock_level) }}</td>
                    <td class="text-end text-muted">{{ number_format($prod->reorder_point) }}</td>
                    <td class="text-end fw-bold text-primary">
                        +{{ number_format($deficit) }} {{ $prod->uom }}
                    </td>
                    <td>
                        @if($prod->total_quantity_on_hand <= 0)
                            <span class="badge badge-soft-danger">Out of Stock</span>
                        @elseif($prod->total_quantity_on_hand <= $prod->min_stock_level)
                            <span class="badge badge-soft-danger">Critical Shortage</span>
                        @else
                            <span class="badge badge-soft-warning">Reorder Triggered</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('inbound.create') }}?product_id={{ $prod->id }}&suggested_qty={{ $deficit }}" class="btn btn-sm btn-outline-primary py-1 px-2 fs-12">
                            <i class="bi bi-cart-plus me-1"></i> Order
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                        Excellent! No inventory items currently suffer from shortages.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())
    <div class="card-footer bg-white d-flex justify-content-end py-2">
        {{ $products->links() }}
    </div>
    @endif
</div>
@endsection
