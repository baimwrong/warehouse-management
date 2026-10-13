@extends('layouts.app')

@section('title', $warehouse->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('warehouses.index') }}" class="text-decoration-none fs-13 text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to Facilities
        </a>
        <h4 class="fw-bold mt-2 mb-0">{{ $warehouse->name }} <span class="badge bg-primary fs-12 ms-2">{{ $warehouse->code }}</span></h4>
        <div class="text-muted fs-13">{{ $warehouse->address }}, {{ $warehouse->city }}, {{ $warehouse->state }} {{ $warehouse->postal_code }}, {{ $warehouse->country }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('locations.index', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-boxes me-1"></i> View Storage Bins
        </a>
        <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit Facility
        </a>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="stat-card">
            <div class="text-muted fs-12 text-uppercase fw-semibold">Capacity Utilization</div>
            <div class="fs-4 fw-bold mt-1 text-primary">{{ $warehouse->capacity_utilization_percent }}%</div>
            <div class="fs-12 text-muted mt-1">{{ number_format($warehouse->occupied_capacity, 1) }} of {{ number_format($warehouse->capacity_cbm, 1) }} CBM</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card">
            <div class="text-muted fs-12 text-uppercase fw-semibold">Total Storage Bins</div>
            <div class="fs-4 fw-bold mt-1">{{ $totalBins }}</div>
            <div class="fs-12 text-muted mt-1">{{ $occupiedBins }} occupied, {{ $totalBins - $occupiedBins }} vacant</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card">
            <div class="text-muted fs-12 text-uppercase fw-semibold">Distinct SKUs Stored</div>
            <div class="fs-4 fw-bold mt-1 text-success">{{ $totalSkus }}</div>
            <div class="fs-12 text-muted mt-1">Catalog items</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="stat-card">
            <div class="text-muted fs-12 text-uppercase fw-semibold">Total Stock Units</div>
            <div class="fs-4 fw-bold mt-1 text-info">{{ number_format($totalItems) }}</div>
            <div class="fs-12 text-muted mt-1">Units on floor</div>
        </div>
    </div>
</div>

<!-- Zones list -->
<div class="card card-wms mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-diagram-2 text-primary me-2"></i> Operational Zones</span>
        <a href="{{ route('locations.create') }}?warehouse_id={{ $warehouse->id }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Storage Bin
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Zone Code</th>
                    <th>Zone Name</th>
                    <th>Type</th>
                    <th>Storage Bins Count</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($warehouse->zones as $zone)
                <tr>
                    <td><span class="badge badge-soft-primary">{{ $zone->code }}</span></td>
                    <td class="fw-semibold">{{ $zone->name }}</td>
                    <td><span class="badge badge-soft-secondary text-capitalize">{{ str_replace('_', ' ', $zone->zone_type) }}</span></td>
                    <td class="fw-bold">{{ $zone->storageLocations->count() }}</td>
                    <td class="text-muted fs-13">{{ $zone->description ?? 'Standard operational zone' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No zones defined for this warehouse yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
