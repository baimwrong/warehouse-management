@extends('layouts.app')

@section('title', 'Stock Adjustments & Cycle Counts')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-sliders text-primary me-2"></i> Stock Adjustments &amp; Cycle Counts</h4>
        <p class="text-muted fs-14 mb-0">Discrepancy reconciliation, write-offs, damaged goods, and approval workflows</p>
    </div>
    <a href="{{ route('adjustments.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Stock Adjustment
    </a>
</div>

<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Adjustment #</th>
                    <th>Warehouse</th>
                    <th>Reason</th>
                    <th>Requested By</th>
                    <th>Line Items</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($adjustments as $adj)
                <tr>
                    <td>
                        <a href="{{ route('adjustments.show', $adj) }}" class="fw-bold text-dark font-monospace text-decoration-none">
                            {{ $adj->adjustment_number }}
                        </a>
                    </td>
                    <td>{{ $adj->warehouse->name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge badge-soft-secondary text-capitalize">{{ str_replace('_', ' ', $adj->reason) }}</span>
                    </td>
                    <td>{{ $adj->requester->name ?? 'Staff' }}</td>
                    <td><span class="badge bg-light text-dark border">{{ $adj->items->count() }} items</span></td>
                    <td>
                        @if($adj->status === 'approved')
                            <span class="badge badge-soft-success"><i class="bi bi-check-circle me-1"></i> Approved</span>
                        @elseif($adj->status === 'rejected')
                            <span class="badge badge-soft-danger"><i class="bi bi-x-circle me-1"></i> Rejected</span>
                        @else
                            <span class="badge badge-soft-warning"><i class="bi bi-clock me-1"></i> Pending Approval</span>
                        @endif
                    </td>
                    <td class="text-muted fs-12">{{ $adj->created_at->format('Y-m-d H:i') }}</td>
                    <td class="text-end">
                        <a href="{{ route('adjustments.show', $adj) }}" class="btn btn-sm btn-outline-secondary py-1 px-2 fs-12">
                            View &amp; Review
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No stock adjustments requested yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($adjustments->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $adjustments->links() }}
    </div>
    @endif
</div>
@endsection
