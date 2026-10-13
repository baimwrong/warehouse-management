@extends('layouts.app')

@section('title', 'Storage Utilization Report')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-pie-chart text-primary me-2"></i> Facility Space Utilization Analysis</h4>
        <p class="text-muted fs-14 mb-0">Analysis of cubic meter capacity, rack occupancy percentages, and floor density</p>
    </div>
</div>

<div class="row g-4">
    @foreach($warehouses as $wh)
    <div class="col-lg-6">
        <div class="card card-wms h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-15">{{ $wh->name }} ({{ $wh->code }})</span>
                <span class="badge {{ $wh->capacity_utilization_percent > 80 ? 'bg-danger' : 'bg-primary' }}">
                    {{ $wh->capacity_utilization_percent }}% Utilized
                </span>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex justify-content-between fs-12 mb-1">
                        <span>Physical Volume Utilization</span>
                        <span class="fw-bold">{{ number_format($wh->occupied_capacity, 1) }} / {{ number_format($wh->capacity_cbm, 1) }} CBM</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar {{ $wh->capacity_utilization_percent > 80 ? 'bg-danger' : 'bg-primary' }}" 
                             style="width: {{ $wh->capacity_utilization_percent }}%"></div>
                    </div>
                </div>

                <h6 class="fw-bold fs-13 text-muted text-uppercase mb-2">Zone Breakdown</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle fs-13 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Zone</th>
                                <th>Type</th>
                                <th class="text-end">Bins</th>
                                <th class="text-end">Occupied</th>
                                <th class="text-end">Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($wh->zones as $zn)
                            @php
                                $totalBins = $zn->storageLocations->count();
                                $occBins = $zn->storageLocations->where('is_occupied', true)->count();
                                $rate = $totalBins > 0 ? round(($occBins / $totalBins) * 100, 1) : 0;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $zn->name }}</td>
                                <td><span class="badge badge-soft-secondary text-capitalize">{{ str_replace('_', ' ', $zn->zone_type) }}</span></td>
                                <td class="text-end">{{ $totalBins }}</td>
                                <td class="text-end fw-bold">{{ $occBins }}</td>
                                <td class="text-end fw-bold {{ $rate > 80 ? 'text-danger' : 'text-primary' }}">{{ $rate }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
