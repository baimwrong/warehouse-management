@extends('layouts.app')

@section('title', 'Warehouse Facilities')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Warehouse Facilities</h4>
        <p class="text-muted fs-14 mb-0">Multi-warehouse facility network and zone configurations</p>
    </div>
    <a href="{{ route('warehouses.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Facility
    </a>
</div>

<div class="row g-4">
    @forelse($warehouses as $wh)
    <div class="col-md-6 col-xl-4">
        <div class="card card-wms h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge bg-primary text-white text-uppercase fs-11 px-2 py-1 mb-1">{{ $wh->code }}</span>
                        <h5 class="fw-bold mb-0">
                            <a href="{{ route('warehouses.show', $wh) }}" class="text-dark text-decoration-none">
                                {{ $wh->name }}
                            </a>
                        </h5>
                    </div>
                    <span class="badge {{ $wh->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }} text-capitalize">
                        {{ $wh->status }}
                    </span>
                </div>

                <div class="text-muted fs-13 mb-3">
                    <i class="bi bi-geo-alt me-1 text-secondary"></i> {{ $wh->city }}, {{ $wh->state ?? $wh->country }}
                </div>

                <div class="bg-light p-3 rounded mb-3">
                    <div class="d-flex justify-content-between fs-12 mb-1">
                        <span class="text-muted">Storage Capacity Utilization</span>
                        <span class="fw-bold">{{ $wh->capacity_utilization_percent }}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar {{ $wh->capacity_utilization_percent > 85 ? 'bg-danger' : ($wh->capacity_utilization_percent > 65 ? 'bg-warning' : 'bg-primary') }}" 
                             style="width: {{ $wh->capacity_utilization_percent }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between fs-11 text-muted mt-1">
                        <span>{{ number_format($wh->occupied_capacity, 1) }} CBM used</span>
                        <span>{{ number_format($wh->capacity_cbm, 1) }} CBM max</span>
                    </div>
                </div>

                <div class="row text-center border-top pt-3 g-2 fs-13">
                    <div class="col-6 border-end">
                        <div class="fw-bold text-dark">{{ $wh->zones_count }}</div>
                        <div class="text-muted fs-11">Operational Zones</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold text-dark">{{ $wh->storage_locations_count }}</div>
                        <div class="text-muted fs-11">Storage Bins</div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-between">
                <a href="{{ route('locations.index', ['warehouse_id' => $wh->id]) }}" class="btn btn-sm btn-outline-secondary fs-12">
                    <i class="bi bi-boxes me-1"></i> Bins
                </a>
                <div class="d-flex gap-2">
                    <a href="{{ route('warehouses.edit', $wh) }}" class="btn btn-sm btn-outline-secondary fs-12">Edit</a>
                    <a href="{{ route('warehouses.show', $wh) }}" class="btn btn-sm btn-primary fs-12">View Hub</a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-building fs-1 d-block mb-2"></i>
        No warehouses provisioned yet.
    </div>
    @endforelse
</div>
@endsection
