@extends('layouts.app')

@section('title', 'Add Staff Member')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.users.index') }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to Staff List
    </a>
    <h4 class="fw-bold mt-2 mb-1">Add New Staff Member</h4>
    <p class="text-muted fs-14 mb-0">Create credentials and assign warehouse permissions to an employee</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-wms p-4">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label fs-13 fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label fs-13 fw-semibold">Work Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label fs-13 fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                        <div class="fs-11 text-muted mt-1">Minimum 8 characters</div>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label fs-13 fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="8">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="role" class="form-label fs-13 fw-semibold">Role &amp; Permissions <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                            <option value="operator" {{ old('role') === 'operator' ? 'selected' : '' }}>Inventory Operator (Receiving, Putaway, Cycle Counts)</option>
                            <option value="dispatcher" {{ old('role') === 'dispatcher' ? 'selected' : '' }}>Lead Dispatcher (Picking, Packing, Shipments)</option>
                            <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }}>Warehouse Manager (Full Facility Operations &amp; Approvals)</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Super Admin (Unrestricted System Access)</option>
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="warehouse_id" class="form-label fs-13 fw-semibold">Assigned Facility</label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror">
                            <option value="">-- Global / All Facilities --</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->code }})</option>
                            @endforeach
                        </select>
                        <div class="fs-11 text-muted mt-1">Leave empty for multi-facility access</div>
                        @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="phone" class="form-label fs-13 fw-semibold">Contact Phone</label>
                        <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="+1-555-010-0000">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 d-flex align-items-center pt-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label fs-13 fw-semibold" for="is_active">Active Account (Can log in)</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Create Staff Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
