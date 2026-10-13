@extends('layouts.app')

@section('title', 'Suppliers Directory')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-truck text-primary me-2"></i> Approved Suppliers</h4>
        <p class="text-muted fs-14 mb-0">Vendor master directory, payment terms, and procurement contact info</p>
    </div>
    <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Supplier
    </a>
</div>

<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Vendor Code</th>
                    <th>Supplier Name</th>
                    <th>Primary Contact</th>
                    <th>Email &amp; Phone</th>
                    <th>Location</th>
                    <th>Payment Terms</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $sup)
                <tr>
                    <td><span class="badge badge-soft-primary font-monospace">{{ $sup->code }}</span></td>
                    <td class="fw-bold text-dark">{{ $sup->name }}</td>
                    <td>{{ $sup->contact_person ?? '&mdash;' }}</td>
                    <td class="fs-13">
                        <div><i class="bi bi-envelope text-muted me-1"></i>{{ $sup->email ?? '-' }}</div>
                        <div class="text-muted fs-11"><i class="bi bi-telephone text-muted me-1"></i>{{ $sup->phone ?? '-' }}</div>
                    </td>
                    <td class="fs-13">{{ $sup->city ?? '' }}, {{ $sup->country }}</td>
                    <td><span class="badge bg-light text-dark border">{{ $sup->payment_terms }}</span></td>
                    <td>
                        <span class="badge {{ $sup->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }} text-capitalize">
                            {{ $sup->status }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('suppliers.edit', $sup) }}" class="btn btn-sm btn-outline-secondary py-1 px-2 fs-12">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No suppliers registered.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($suppliers->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $suppliers->links() }}
    </div>
    @endif
</div>
@endsection
