@extends('layouts.app')

@section('title', 'Edit ' . $customer->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('customers.index') }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to Customers
    </a>
    <h4 class="fw-bold mt-2">Edit Customer</h4>
</div>

<div class="card card-wms" style="max-width: 800px;">
    <div class="card-body p-4">
        <form action="{{ route('customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="code" class="form-label fs-13 fw-semibold">Customer Code</label>
                    <input type="text" name="code" id="code" class="form-control font-monospace text-uppercase" value="{{ old('code', $customer->code) }}" required>
                </div>
                <div class="col-md-8">
                    <label for="name" class="form-label fs-13 fw-semibold">Customer Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="contact_person" class="form-label fs-13 fw-semibold">Contact Person</label>
                    <input type="text" name="contact_person" id="contact_person" class="form-control" value="{{ old('contact_person', $customer->contact_person) }}">
                </div>
                <div class="col-md-4">
                    <label for="email" class="form-label fs-13 fw-semibold">Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $customer->email) }}">
                </div>
                <div class="col-md-4">
                    <label for="phone" class="form-label fs-13 fw-semibold">Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $customer->phone) }}">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="shipping_address" class="form-label fs-13 fw-semibold">Shipping Address</label>
                    <textarea name="shipping_address" id="shipping_address" rows="2" class="form-control">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label for="billing_address" class="form-label fs-13 fw-semibold">Billing Address</label>
                    <textarea name="billing_address" id="billing_address" rows="2" class="form-control">{{ old('billing_address', $customer->billing_address) }}</textarea>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="city" class="form-label fs-13 fw-semibold">City</label>
                    <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $customer->city) }}">
                </div>
                <div class="col-md-4">
                    <label for="country" class="form-label fs-13 fw-semibold">Country</label>
                    <input type="text" name="country" id="country" class="form-control" value="{{ old('country', $customer->country) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label fs-13 fw-semibold">Status</label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $customer->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Update Customer</button>
            </div>
        </form>
    </div>
</div>
@endsection
