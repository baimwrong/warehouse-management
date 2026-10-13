@extends('layouts.app')

@section('title', 'Customers Directory')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-badge text-primary me-2"></i> Client &amp; Customer Accounts</h4>
        <p class="text-muted fs-14 mb-0">Master customer registry, shipping addresses, and outbound delivery details</p>
    </div>
    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Customer
    </a>
</div>

<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Customer Name</th>
                    <th>Contact Person</th>
                    <th>Email &amp; Phone</th>
                    <th>Shipping Destination</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                <tr>
                    <td><span class="badge badge-soft-primary font-monospace">{{ $c->code }}</span></td>
                    <td class="fw-bold text-dark">{{ $c->name }}</td>
                    <td>{{ $c->contact_person ?? '&mdash;' }}</td>
                    <td class="fs-13">
                        <div><i class="bi bi-envelope text-muted me-1"></i>{{ $c->email ?? '-' }}</div>
                        <div class="text-muted fs-11"><i class="bi bi-telephone text-muted me-1"></i>{{ $c->phone ?? '-' }}</div>
                    </td>
                    <td class="fs-13">
                        <div class="text-truncate" style="max-width: 250px;" title="{{ $c->shipping_address }}">
                            {{ $c->shipping_address ?? 'No address set' }}
                        </div>
                        <div class="text-muted fs-11">{{ $c->city }}, {{ $c->country }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $c->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }} text-capitalize">
                            {{ $c->status }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('customers.edit', $c) }}" class="btn btn-sm btn-outline-secondary py-1 px-2 fs-12">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No customers registered yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $customers->links() }}
    </div>
    @endif
</div>
@endsection
