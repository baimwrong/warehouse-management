@extends('layouts.app')

@section('title', 'Inbound Receiving & POs')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-box-arrow-in-down text-primary me-2"></i> Inbound Receiving &amp; Purchase Orders</h4>
        <p class="text-muted fs-14 mb-0">Manage incoming supplier freight, dock unloading, and putaway inspection</p>
    </div>
    <a href="{{ route('inbound.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Inbound Order (PO)
    </a>
</div>

<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>PO Order Number</th>
                    <th>Supplier</th>
                    <th>Target Facility</th>
                    <th>Expected Date</th>
                    <th class="text-end">Total Valuation</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $ord)
                <tr>
                    <td>
                        <a href="{{ route('inbound.show', $ord) }}" class="fw-bold text-dark font-monospace text-decoration-none">
                            {{ $ord->order_number }}
                        </a>
                        <div class="text-muted fs-11">Items: {{ $ord->items->count() }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $ord->supplier->name ?? 'N/A' }}</div>
                        <div class="text-muted fs-11">{{ $ord->supplier->code ?? '' }}</div>
                    </td>
                    <td>{{ $ord->warehouse->name ?? 'N/A' }}</td>
                    <td class="text-muted fs-13">{{ $ord->expected_date ? $ord->expected_date->format('Y-m-d') : 'Immediate' }}</td>
                    <td class="text-end fw-bold">${{ number_format($ord->total_amount, 2) }}</td>
                    <td>
                        @if($ord->status === 'received')
                            <span class="badge badge-soft-success"><i class="bi bi-check-circle me-1"></i> Received</span>
                        @elseif($ord->status === 'partially_received')
                            <span class="badge badge-soft-info"><i class="bi bi-pie-chart me-1"></i> Partial</span>
                        @elseif($ord->status === 'cancelled')
                            <span class="badge badge-soft-danger">Cancelled</span>
                        @else
                            <span class="badge badge-soft-warning"><i class="bi bi-clock me-1"></i> Pending Receipt</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            @if(in_array($ord->status, ['pending_receipt', 'partially_received']))
                            <a href="{{ route('inbound.receive', $ord) }}" class="btn btn-sm btn-primary py-1 px-2 fs-12">
                                <i class="bi bi-box-arrow-in-down me-1"></i> Receive &amp; Putaway
                            </a>
                            @endif
                            <a href="{{ route('inbound.show', $ord) }}" class="btn btn-sm btn-outline-secondary py-1 px-2 fs-12">
                                Details
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No inbound orders recorded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
