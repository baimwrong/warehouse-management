@extends('layouts.app')

@section('title', 'Add Supplier')

@section('content')
<div class="mb-4">
    <a href="{{ route('suppliers.index') }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to Suppliers
    </a>
    <h4 class="fw-bold mt-2">Register Approved Supplier</h4>
</div>

<div class="card card-wms" style="max-width: 800px;">
    <div class="card-body p-4">
        <form action="{{ route('suppliers.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="code" class="form-label fs-13 fw-semibold">Supplier Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="code" class="form-control font-monospace text-uppercase" placeholder="SUPP-XXX-01" value="{{ old('code') }}" required>
                </div>
                <div class="col-md-8">
                    <label for="name" class="form-label fs-13 fw-semibold">Company / Supplier Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Acme Components International" value="{{ old('name') }}" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="contact_person" class="form-label fs-13 fw-semibold">Contact Person</label>
                    <input type="text" name="contact_person" id="contact_person" class="form-control" value="{{ old('contact_person') }}">
                </div>
                <div class="col-md-4">
                    <label for="email" class="form-label fs-13 fw-semibold">Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}">
                </div>
                <div class="col-md-4">
                    <label for="phone" class="form-label fs-13 fw-semibold">Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}">
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label fs-13 fw-semibold">Address</label>
                <input type="text" name="address" id="address" class="form-control" value="{{ old('address') }}">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="city" class="form-label fs-13 fw-semibold">City</label>
                    <input type="text" name="city" id="city" class="form-control" value="{{ old('city') }}">
                </div>
                <div class="col-md-6">
                    <label for="country" class="form-label fs-13 fw-semibold">Country <span class="text-danger">*</span></label>
                    <input type="text" name="country" id="country" class="form-control" value="{{ old('country', 'United States') }}" required>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="tax_id" class="form-label fs-13 fw-semibold">Tax ID / VAT</label>
                    <input type="text" name="tax_id" id="tax_id" class="form-control" value="{{ old('tax_id') }}">
                </div>
                <div class="col-md-4">
                    <label for="payment_terms" class="form-label fs-13 fw-semibold">Payment Terms</label>
                    <input type="text" name="payment_terms" id="payment_terms" class="form-control" value="{{ old('payment_terms', 'Net 30') }}">
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label fs-13 fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Save Supplier</button>
            </div>
        </form>
    </div>
</div>
@endsection
