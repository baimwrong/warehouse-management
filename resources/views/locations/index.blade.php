@extends('layouts.app')

@section('title', 'Storage Bins & Locations')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Storage Bins &amp; Locations</h4>
        <p class="text-muted fs-14 mb-0">Granular warehouse locations, rack coordinates, and occupancy status</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('locations.visual-map') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-diagram-3 me-1"></i> 2D Visual Rack Map
        </a>
        <a href="{{ route('locations.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Storage Bin
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card card-wms mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('locations.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search bin or barcode..." value="{{ $search }}">
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
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- All Statuses --</option>
                    <option value="occupied" {{ $status === 'occupied' ? 'selected' : '' }}>Occupied Bins</option>
                    <option value="vacant" {{ $status === 'vacant' ? 'selected' : '' }}>Vacant Bins</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('locations.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Location Code</th>
                    <th>Warehouse / Zone</th>
                    <th>Coordinates</th>
                    <th>Max Limits</th>
                    <th>Current Contents</th>
                    <th>Occupancy</th>
                </tr>
            </thead>
            <tbody>
                @forelse($locations as $loc)
                <tr>
                    <td>
                        <span class="fw-bold font-monospace text-dark">{{ $loc->location_code }}</span>
                        @if($loc->barcode)
                            <div class="text-muted fs-11"><i class="bi bi-upc me-1"></i>{{ $loc->barcode }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $loc->warehouse->name ?? 'N/A' }}</div>
                        <div class="text-muted fs-12">{{ $loc->zone->name ?? '' }} ({{ $loc->zone->code ?? '' }})</div>
                    </td>
                    <td class="fs-12 text-muted">
                        Aisle: <strong>{{ $loc->aisle ?? '-' }}</strong> &bull;
                        Rack: <strong>{{ $loc->rack ?? '-' }}</strong> &bull;
                        Shelf: <strong>{{ $loc->shelf ?? '-' }}</strong> &bull;
                        Bin: <strong>{{ $loc->bin ?? '-' }}</strong>
                    </td>
                    <td class="fs-12">
                        <div>{{ number_format($loc->max_weight_kg) }} kg max</div>
                        <div class="text-muted">{{ number_format($loc->max_volume_cbm, 2) }} CBM</div>
                    </td>
                    <td>
                        @if($loc->stocks->where('quantity_on_hand', '>', 0)->count() > 0)
                            @foreach($loc->stocks->where('quantity_on_hand', '>', 0) as $stock)
                                <div class="fs-12">
                                    <span class="fw-semibold">{{ $stock->product->name ?? 'Unknown SKU' }}</span>:
                                    <span class="badge bg-secondary">{{ number_format($stock->quantity_on_hand) }} {{ $stock->product->uom ?? '' }}</span>
                                </div>
                            @endforeach
                        @else
                            <span class="text-muted fs-12 fst-italic">Empty bin</span>
                        @endif
                    </td>
                    <td>
                        @if($loc->is_occupied)
                            <span class="badge badge-soft-danger"><i class="bi bi-lock-fill me-1"></i> Occupied</span>
                        @else
                            <span class="badge badge-soft-success"><i class="bi bi-check-circle me-1"></i> Vacant</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No storage locations match your filter criteria.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($locations->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $locations->links() }}
    </div>
    @endif
</div>
@endsection
