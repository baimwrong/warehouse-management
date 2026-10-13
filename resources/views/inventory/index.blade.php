@extends('layouts.app')

@section('title', 'Physical Stock on Hand')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Physical Stock on Hand</h4>
        <p class="text-muted fs-14 mb-0">Real-time inventory levels allocated per warehouse, zone, bin, and batch lot</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('movements.transfer') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left-right me-1"></i> Bin Transfer
        </a>
        <a href="{{ route('inventory.low-stock') }}" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-exclamation-triangle me-1"></i> Low Stock Warnings
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card card-wms mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('inventory.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search SKU, product name, or bin code..." value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select name="warehouse_id" class="form-select form-select-sm">
                    <option value="">-- All Warehouses --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Warehouse / Bin</th>
                    <th>Product / SKU</th>
                    <th>Lot / Batch</th>
                    <th class="text-end">On-Hand Qty</th>
                    <th class="text-end">Reserved Qty</th>
                    <th class="text-end">Available Qty</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $stock->warehouse->name ?? 'N/A' }}</div>
                        <span class="badge badge-soft-primary font-monospace">{{ $stock->location->location_code ?? 'Bin' }}</span>
                        <span class="text-muted fs-11">({{ $stock->location->zone->name ?? '' }})</span>
                    </td>
                    <td>
                        <a href="{{ route('products.show', $stock->product) }}" class="fw-bold text-dark text-decoration-none">
                            {{ $stock->product->name ?? 'Unknown SKU' }}
                        </a>
                        <div class="text-muted fs-12 font-monospace">{{ $stock->product->sku ?? '' }}</div>
                    </td>
                    <td>
                        @if($stock->batch)
                            <span class="badge badge-soft-secondary font-monospace">{{ $stock->batch->batch_number }}</span>
                            @if($stock->batch->expiry_date)
                                <div class="text-muted fs-11">Exp: {{ $stock->batch->expiry_date->format('Y-m-d') }}</div>
                            @endif
                        @else
                            <span class="text-muted fs-12">&mdash;</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold fs-14">
                        {{ number_format($stock->quantity_on_hand) }} {{ $stock->product->uom ?? '' }}
                    </td>
                    <td class="text-end text-muted">
                        {{ number_format($stock->quantity_reserved) }}
                    </td>
                    <td class="text-end fw-bold text-success fs-14">
                        {{ number_format($stock->available_quantity) }}
                    </td>
                    <td class="text-end">
                        <a href="{{ route('movements.transfer') }}?product_id={{ $stock->product_id }}&from_location_id={{ $stock->storage_location_id }}&warehouse_id={{ $stock->warehouse_id }}" 
                           class="btn btn-sm btn-outline-secondary py-1 px-2 fs-12" title="Relocate / Transfer">
                            <i class="bi bi-arrow-left-right me-1"></i> Transfer
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No physical inventory matches your query.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($stocks->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $stocks->links() }}
    </div>
    @endif
</div>
@endsection
