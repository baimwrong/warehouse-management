@extends('layouts.app')

@section('title', 'Add Storage Bin')

@section('content')
<div class="mb-4">
    <a href="{{ route('locations.index') }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to Storage Bins
    </a>
    <h4 class="fw-bold mt-2">Provision Storage Bin</h4>
</div>

<div class="card card-wms" style="max-width: 800px;">
    <div class="card-body p-4">
        <form action="{{ route('locations.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="warehouse_id" class="form-label fs-13 fw-semibold">Warehouse Facility <span class="text-danger">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" class="form-select" required onchange="filterZones(this.value)">
                        <option value="">-- Select Facility --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }} ({{ $wh->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="warehouse_zone_id" class="form-label fs-13 fw-semibold">Operational Zone <span class="text-danger">*</span></label>
                    <select name="warehouse_zone_id" id="warehouse_zone_id" class="form-select" required>
                        <option value="">-- Select Zone --</option>
                        @foreach($warehouses as $wh)
                            @foreach($wh->zones as $zone)
                                <option value="{{ $zone->id }}" data-warehouse="{{ $wh->id }}" class="zone-option" {{ old('warehouse_zone_id') == $zone->id ? 'selected' : '' }}>
                                    [{{ $wh->code }}] {{ $zone->name }} ({{ $zone->code }})
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="location_code" class="form-label fs-13 fw-semibold">Storage Bin Location Code <span class="text-danger">*</span></label>
                <input type="text" name="location_code" id="location_code" class="form-control text-uppercase font-monospace" placeholder="e.g. WH01-ZN-A-A01-R01-S01-B01" value="{{ old('location_code') }}" required>
                <div class="form-text fs-12">Must be unique across the entire warehouse system.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="aisle" class="form-label fs-13 fw-semibold">Aisle</label>
                    <input type="text" name="aisle" id="aisle" class="form-control" placeholder="A01" value="{{ old('aisle') }}">
                </div>
                <div class="col-md-3">
                    <label for="rack" class="form-label fs-13 fw-semibold">Rack</label>
                    <input type="text" name="rack" id="rack" class="form-control" placeholder="R01" value="{{ old('rack') }}">
                </div>
                <div class="col-md-3">
                    <label for="shelf" class="form-label fs-13 fw-semibold">Shelf</label>
                    <input type="text" name="shelf" id="shelf" class="form-control" placeholder="S01" value="{{ old('shelf') }}">
                </div>
                <div class="col-md-3">
                    <label for="bin" class="form-label fs-13 fw-semibold">Bin</label>
                    <input type="text" name="bin" id="bin" class="form-control" placeholder="B01" value="{{ old('bin') }}">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="max_weight_kg" class="form-label fs-13 fw-semibold">Max Weight Capacity (KG) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="max_weight_kg" id="max_weight_kg" class="form-control" value="{{ old('max_weight_kg', 1000.0) }}" required>
                </div>
                <div class="col-md-6">
                    <label for="max_volume_cbm" class="form-label fs-13 fw-semibold">Max Volume Capacity (CBM) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="max_volume_cbm" id="max_volume_cbm" class="form-control" value="{{ old('max_volume_cbm', 2.50) }}" required>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('locations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Create Bin Location</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterZones(whId) {
    const options = document.querySelectorAll('.zone-option');
    options.forEach(opt => {
        if (!whId || opt.getAttribute('data-warehouse') === whId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
}
document.addEventListener('DOMContentLoaded', () => {
    const whSelect = document.getElementById('warehouse_id');
    if (whSelect && whSelect.value) filterZones(whSelect.value);
});
</script>
@endsection
