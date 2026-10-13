@extends('layouts.app')

@section('title', 'Movement Audit Report')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary me-2"></i> Stock Movement Audit Report</h4>
        <p class="text-muted fs-14 mb-0">Historical material ledger movements across date intervals</p>
    </div>
</div>

<div class="card card-wms mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.movements') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">-- All Actions --</option>
                    <option value="inbound" {{ $type === 'inbound' ? 'selected' : '' }}>Inbound PO Receipt</option>
                    <option value="outbound" {{ $type === 'outbound' ? 'selected' : '' }}>Outbound Dispatch</option>
                    <option value="transfer" {{ $type === 'transfer' ? 'selected' : '' }}>Internal Bin Transfer</option>
                    <option value="adjustment" {{ $type === 'adjustment' ? 'selected' : '' }}>Cycle Count Adjustment</option>
                    <option value="initial_balance" {{ $type === 'initial_balance' ? 'selected' : '' }}>Initial Balance</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary w-50">Filter</button>
                <a href="{{ route('reports.movements') }}" class="btn btn-sm btn-outline-secondary w-50">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Date / Time</th>
                    <th>Type</th>
                    <th>Product</th>
                    <th class="text-end">Quantity</th>
                    <th>Source Bin</th>
                    <th>Destination Bin</th>
                    <th>Operator</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $mv)
                <tr>
                    <td class="text-nowrap fs-12">{{ $mv->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <span class="badge badge-soft-primary text-capitalize">{{ str_replace('_', ' ', $mv->reference_type) }}</span>
                    </td>
                    <td>
                        <span class="fw-semibold">{{ $mv->product->name ?? 'SKU' }}</span>
                        <div class="text-muted fs-11 font-monospace">{{ $mv->product->sku ?? '' }}</div>
                    </td>
                    <td class="text-end fw-bold">{{ number_format($mv->quantity) }}</td>
                    <td>{{ $mv->fromLocation->location_code ?? '&mdash;' }}</td>
                    <td>{{ $mv->toLocation->location_code ?? '&mdash;' }}</td>
                    <td>{{ $mv->user->name ?? 'System' }}</td>
                    <td class="text-muted fs-12">{{ $mv->notes ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No movement records found for this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $movements->links() }}
    </div>
    @endif
</div>
@endsection
