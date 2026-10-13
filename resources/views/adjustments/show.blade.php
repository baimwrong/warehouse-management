@extends('layouts.app')

@section('title', $adjustment->adjustment_number)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <a href="{{ route('adjustments.index') }}" class="text-decoration-none fs-13 text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to Adjustments
        </a>
        <h4 class="fw-bold mt-2 mb-0 font-monospace">{{ $adjustment->adjustment_number }}</h4>
        <div class="text-muted fs-13">
            Facility: <strong>{{ $adjustment->warehouse->name ?? 'N/A' }}</strong> &bull;
            Reason: <span class="badge badge-soft-secondary text-capitalize">{{ str_replace('_', ' ', $adjustment->reason) }}</span>
        </div>
    </div>

    <!-- Manager/Admin Action Buttons -->
    @if($adjustment->status === 'pending' && (auth()->user()->isAdmin() || auth()->user()->isManager()))
    <div class="d-flex gap-2">
        <form action="{{ route('adjustments.reject', $adjustment) }}" method="POST" onsubmit="return confirm('Are you sure you want to reject this adjustment request?')">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-x-circle me-1"></i> Reject
            </button>
        </form>
        <form action="{{ route('adjustments.approve', $adjustment) }}" method="POST" onsubmit="return confirm('Approve adjustment and commit quantity updates to inventory ledger?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-check-circle me-1"></i> Approve &amp; Commit to Ledger
            </button>
        </form>
    </div>
    @endif
</div>

<!-- Header Info Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card card-wms p-3">
            <div class="text-muted fs-12">Approval Status</div>
            <div class="mt-1">
                @if($adjustment->status === 'approved')
                    <span class="badge badge-soft-success fs-13"><i class="bi bi-check-circle-fill me-1"></i> Approved</span>
                @elseif($adjustment->status === 'rejected')
                    <span class="badge badge-soft-danger fs-13"><i class="bi bi-x-circle-fill me-1"></i> Rejected</span>
                @else
                    <span class="badge badge-soft-warning fs-13"><i class="bi bi-clock-fill me-1"></i> Pending Approval</span>
                @endif
            </div>
            @if($adjustment->approver)
                <div class="text-muted fs-11 mt-1">Reviewed by {{ $adjustment->approver->name }} on {{ $adjustment->approved_at?->format('Y-m-d') }}</div>
            @endif
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card card-wms p-3">
            <div class="text-muted fs-12">Requested By</div>
            <div class="fw-bold fs-14 mt-1">{{ $adjustment->requester->name ?? 'Staff' }}</div>
            <div class="text-muted fs-11">{{ $adjustment->created_at->format('Y-m-d H:i') }}</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card card-wms p-3">
            <div class="text-muted fs-12">Line Items Count</div>
            <div class="fw-bold fs-14 mt-1">{{ $adjustment->items->count() }} items</div>
            <div class="text-muted fs-11">Total audited SKUs</div>
        </div>
    </div>
</div>

@if($adjustment->notes)
<div class="alert alert-light border mb-4 fs-13">
    <strong>Justification Notes:</strong> {{ $adjustment->notes }}
</div>
@endif

<!-- Adjustment Variance Items Table -->
<div class="card card-wms">
    <div class="card-header">
        <i class="bi bi-list-check text-primary me-2"></i> Inventory Count Variance Table
    </div>
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Product / SKU</th>
                    <th>Storage Bin</th>
                    <th class="text-end">Previous Count</th>
                    <th class="text-end">Actual Count</th>
                    <th class="text-end">Difference Variance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($adjustment->items as $item)
                <tr>
                    <td>
                        <a href="{{ route('products.show', $item->product) }}" class="fw-bold text-dark text-decoration-none">
                            {{ $item->product->name }}
                        </a>
                        <div class="font-monospace text-primary fs-12">{{ $item->product->sku }}</div>
                    </td>
                    <td>
                        <span class="badge badge-soft-primary font-monospace">{{ $item->location->location_code }}</span>
                    </td>
                    <td class="text-end text-muted">
                        {{ number_format($item->previous_quantity) }} {{ $item->product->uom }}
                    </td>
                    <td class="text-end fw-bold">
                        {{ number_format($item->new_quantity) }} {{ $item->product->uom }}
                    </td>
                    <td class="text-end fw-bold {{ $item->difference_quantity > 0 ? 'text-success' : ($item->difference_quantity < 0 ? 'text-danger' : 'text-muted') }}">
                        {{ $item->difference_quantity > 0 ? '+' : '' }}{{ number_format($item->difference_quantity) }} {{ $item->product->uom }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
