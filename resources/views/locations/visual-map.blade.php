@extends('layouts.app')

@section('title', '2D Visual Rack & Bin Map')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-diagram-3 text-primary me-2"></i> 2D Visual Rack &amp; Bin Map</h4>
        <p class="text-muted fs-14 mb-0">Interactive visual floor plan of storage racks and bin occupancy states</p>
    </div>

    <!-- Warehouse switcher -->
    <form method="GET" action="{{ route('locations.visual-map') }}" class="d-flex align-items-center gap-2">
        <label for="warehouse_id" class="fs-13 fw-semibold text-muted text-nowrap">Facility:</label>
        <select name="warehouse_id" id="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ $selectedWarehouse?->id == $wh->id ? 'selected' : '' }}>
                    {{ $wh->name }} ({{ $wh->code }})
                </option>
            @endforeach
        </select>
    </form>
</div>

<!-- Legend Bar -->
<div class="card card-wms mb-4 p-3 bg-white">
    <div class="d-flex flex-wrap align-items-center gap-4 fs-13">
        <span class="fw-bold text-muted">Legend:</span>
        <div class="d-flex align-items-center">
            <span class="d-inline-block rounded me-2 border border-success" style="width: 16px; height: 16px; background-color: #f0fdf4;"></span>
            <span>Vacant Storage Bin (Available)</span>
        </div>
        <div class="d-flex align-items-center">
            <span class="d-inline-block rounded me-2 border border-danger" style="width: 16px; height: 16px; background-color: #fef2f2;"></span>
            <span>Occupied Bin (Stock on Hand)</span>
        </div>
        <div class="ms-auto fs-12 text-muted">
            <i class="bi bi-info-circle me-1"></i> Hover or tap any bin to inspect physical inventory details
        </div>
    </div>
</div>

<!-- Zones & Bins Grid -->
@if($selectedWarehouse)
    @forelse($zones as $zone)
    <div class="card card-wms mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary text-white">{{ $zone->code }}</span>
                <span class="fw-bold">{{ $zone->name }}</span>
                <span class="badge badge-soft-secondary text-capitalize fs-11">{{ str_replace('_', ' ', $zone->zone_type) }}</span>
            </div>
            <div class="fs-12 text-muted">
                {{ $zone->storageLocations->where('is_occupied', true)->count() }} / {{ $zone->storageLocations->count() }} Bins Occupied
            </div>
        </div>
        <div class="card-body">
            <div class="bin-grid">
                @forelse($zone->storageLocations as $bin)
                <div class="bin-cell {{ $bin->is_occupied ? 'occupied' : 'vacant' }}" 
                     data-bs-toggle="tooltip" 
                     data-bs-html="true" 
                     title="<div class='text-start p-1'><strong>{{ $bin->location_code }}</strong><br>Aisle: {{ $bin->aisle ?? '-' }} | Rack: {{ $bin->rack ?? '-' }}<br>Shelf: {{ $bin->shelf ?? '-' }} | Bin: {{ $bin->bin ?? '-' }}<br><hr class='my-1'>@if($bin->stocks->count() > 0)@foreach($bin->stocks as $stk)Item: {{ $stk->product->name ?? 'SKU' }}<br>Qty: {{ number_format($stk->quantity_on_hand) }} {{ $stk->product->uom ?? '' }}<br>@endforeach @else Empty Bin @endif</div>">
                    <div class="bin-code">{{ $bin->bin ?? $bin->location_code }}</div>
                    <div class="fs-11 text-muted">{{ $bin->aisle ?? '' }}-{{ $bin->rack ?? '' }}</div>
                    <div class="mt-1">
                        @if($bin->is_occupied)
                            <span class="badge bg-danger text-white fs-10 px-1 py-0.5">Occupied</span>
                        @else
                            <span class="badge bg-success text-white fs-10 px-1 py-0.5">Vacant</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-muted fs-13 py-3">No storage bins provisioned inside this zone yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    @empty
    <div class="card card-wms p-5 text-center text-muted">
        <i class="bi bi-diagram-3 fs-1 mb-2"></i>
        No operational zones created for this facility yet.
    </div>
    @endforelse
@endif
@endsection
