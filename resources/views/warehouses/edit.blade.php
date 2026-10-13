@extends('layouts.app')

@section('title', 'Edit ' . $warehouse->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('warehouses.show', $warehouse) }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to {{ $warehouse->name }}
    </a>
    <h4 class="fw-bold mt-2">Edit Warehouse Facility</h4>
</div>

<div class="card card-wms" style="max-width: 800px;">
    <div class="card-body p-4">
        <form action="{{ route('warehouses.update', $warehouse) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="code" class="form-label fs-13 fw-semibold">Facility Code</label>
                    <input type="text" name="code" id="code" class="form-control text-uppercase" value="{{ old('code', $warehouse->code) }}" required>
                </div>
                <div class="col-md-8">
                    <label for="name" class="form-label fs-13 fw-semibold">Warehouse Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $warehouse->name) }}" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label fs-13 fw-semibold">Street Address</label>
                <input type="text" name="address" id="address" class="form-control" value="{{ old('address', $warehouse->address) }}">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="city" class="form-label fs-13 fw-semibold">City</label>
                    <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $warehouse->city) }}">
                </div>
                <div class="col-md-4">
                    <label for="state" class="form-label fs-13 fw-semibold">State / Province</label>
                    <input type="text" name="state" id="state" class="form-control" value="{{ old('state', $warehouse->state) }}">
                </div>
                <div class="col-md-4">
                    <label for="postal_code" class="form-label fs-13 fw-semibold">Postal / ZIP Code</label>
                    <input type="text" name="postal_code" id="postal_code" class="form-control" value="{{ old('postal_code', $warehouse->postal_code) }}">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="country" class="form-label fs-13 fw-semibold">Country</label>
                    <input type="text" name="country" id="country" class="form-control" value="{{ old('country', $warehouse->country) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="capacity_cbm" class="form-label fs-13 fw-semibold">Total Capacity (CBM)</label>
                    <input type="number" step="0.01" name="capacity_cbm" id="capacity_cbm" class="form-control" value="{{ old('capacity_cbm', $warehouse->capacity_cbm) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label fs-13 fw-semibold">Status</label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="active" {{ old('status', $warehouse->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $warehouse->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="maintenance" {{ old('status', $warehouse->status) === 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="contact_email" class="form-label fs-13 fw-semibold">Facility Email</label>
                    <input type="email" name="contact_email" id="contact_email" class="form-control" value="{{ old('contact_email', $warehouse->contact_email) }}">
                </div>
                <div class="col-md-6">
                    <label for="contact_phone" class="form-label fs-13 fw-semibold">Facility Phone</label>
                    <input type="text" name="contact_phone" id="contact_phone" class="form-control" value="{{ old('contact_phone', $warehouse->contact_phone) }}">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Update Facility</button>
            </div>
        </form>
    </div>
</div>
@endsection
