@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <a href="{{ route('products.index') }}" class="text-decoration-none fs-13 text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to Products
        </a>
        <h4 class="fw-bold mt-2 mb-0">{{ $product->name }}</h4>
        <div class="text-muted fs-13">
            SKU: <span class="font-monospace text-primary fw-semibold">{{ $product->sku }}</span> &bull;
            Category: <span class="badge badge-soft-primary">{{ $product->category->name ?? 'General' }}</span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('movements.transfer') }}?product_id={{ $product->id }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left-right me-1"></i> Transfer Stock
        </a>
        <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Left Details Card -->
    <div class="col-lg-8">
        <div class="card card-wms mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle text-primary me-2"></i> Product Specifications
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="text-muted fs-12">Unit of Measure</div>
                        <div class="fw-bold fs-14">{{ $product->uom }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted fs-12">Unit Cost (Standard)</div>
                        <div class="fw-bold fs-14 text-dark">${{ number_format($product->unit_cost, 2) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted fs-12">MSRP Price</div>
                        <div class="fw-bold fs-14 text-dark">${{ number_format($product->unit_price, 2) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted fs-12">Unit Weight</div>
                        <div class="fw-bold fs-14">{{ number_format($product->weight_kg, 3) }} kg</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted fs-12">Unit Volume</div>
                        <div class="fw-bold fs-14">{{ number_format($product->volume_cbm, 4) }} CBM</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted fs-12">Total Valuation</div>
                        <div class="fw-bold fs-14 text-success">${{ number_format($product->total_valuation, 2) }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted fs-12">Safety Min Level</div>
                        <div class="fw-bold fs-14 text-danger">{{ number_format($product->min_stock_level) }} {{ $product->uom }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted fs-12">Reorder Point</div>
                        <div class="fw-bold fs-14 text-warning">{{ number_format($product->reorder_point) }} {{ $product->uom }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted fs-12">Max Ceiling Level</div>
                        <div class="fw-bold fs-14">{{ number_format($product->max_stock_level) }} {{ $product->uom }}</div>
                    </div>
                </div>

                @if($product->description)
                <div class="mt-3 pt-3 border-top">
                    <div class="text-muted fs-12 mb-1">Description</div>
                    <div class="fs-13">{{ $product->description }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Inventory Locations Breakdown -->
        <div class="card card-wms">
            <div class="card-header">
                <i class="bi bi-geo-alt text-primary me-2"></i> Current Stock In Storage Bins
            </div>
            <div class="table-responsive">
                <table class="table table-wms align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Warehouse</th>
                            <th>Storage Bin</th>
                            <th>Batch / Lot</th>
                            <th class="text-end">On-Hand Qty</th>
                            <th class="text-end">Reserved Qty</th>
                            <th class="text-end">Available Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($product->stocks->where('quantity_on_hand', '>', 0) as $stock)
                        <tr>
                            <td class="fw-semibold">{{ $stock->warehouse->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-soft-primary font-monospace">{{ $stock->location->location_code ?? 'Unassigned' }}</span>
                                <div class="text-muted fs-11">{{ $stock->location->zone->name ?? '' }}</div>
                            </td>
                            <td>
                                @if($stock->batch)
                                    <span class="badge badge-soft-secondary">{{ $stock->batch->batch_number }}</span>
                                @else
                                    <span class="text-muted fs-12">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">{{ number_format($stock->quantity_on_hand) }}</td>
                            <td class="text-end text-muted">{{ number_format($stock->quantity_reserved) }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($stock->available_quantity) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No physical inventory on hand currently.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Barcode Card -->
    <div class="col-lg-4">
        <div class="card card-wms text-center p-4 mb-4">
            <h6 class="fw-bold mb-3">Offline Barcode Label</h6>
            <div class="p-3 bg-white border rounded d-inline-block mx-auto mb-3">
                {!! $barcodeSvg !!}
            </div>
            <div class="fs-12 text-muted">
                Scan or print this label for bin tagging and material tracking.
            </div>
        </div>

        <!-- Batches Card -->
        <div class="card card-wms">
            <div class="card-header">
                <i class="bi bi-calendar-check text-primary me-2"></i> Product Lots / Batches
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush fs-13">
                    @forelse($product->batches as $batch)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">{{ $batch->batch_number }}</div>
                            <div class="text-muted fs-11">
                                Exp: {{ $batch->expiry_date ? $batch->expiry_date->format('M Y') : 'No expiry' }}
                            </div>
                        </div>
                        @if($batch->is_expired)
                            <span class="badge badge-soft-danger">Expired</span>
                        @elseif($batch->days_until_expiration && $batch->days_until_expiration < 60)
                            <span class="badge badge-soft-warning">{{ $batch->days_until_expiration }} days left</span>
                        @else
                            <span class="badge badge-soft-success">Active</span>
                        @endif
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-3">No lot numbers recorded.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
