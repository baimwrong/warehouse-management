@extends('layouts.app')

@section('title', 'Outbound Orders & Dispatch')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-box-arrow-up-right text-primary me-2"></i> Outbound Orders &amp; Dispatch</h4>
        <p class="text-muted fs-14 mb-0">Customer sales orders, wave picking, packing verification, and carrier dispatch</p>
    </div>
    <a href="{{ route('outbound.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Sales Order (SO)
    </a>
</div>

<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Facility</th>
                    <th>Priority</th>
                    <th>Carrier / Tracking</th>
                    <th class="text-end">Valuation</th>
                    <th>Fulfillment Progress</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $ord)
                <tr>
                    <td>
                        <a href="{{ route('outbound.show', $ord) }}" class="fw-bold text-dark font-monospace text-decoration-none">
                            {{ $ord->order_number }}
                        </a>
                        <div class="text-muted fs-11">Items: {{ $ord->items->count() }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $ord->customer->name ?? 'N/A' }}</div>
                        <div class="text-muted fs-11">{{ $ord->customer->city ?? '' }}</div>
                    </td>
                    <td>{{ $ord->warehouse->name ?? 'N/A' }}</td>
                    <td>
                        @if($ord->priority === 'urgent')
                            <span class="badge bg-danger text-uppercase fs-10">Urgent</span>
                        @elseif($ord->priority === 'high')
                            <span class="badge bg-warning text-dark text-uppercase fs-10">High</span>
                        @else
                            <span class="badge bg-secondary text-uppercase fs-10">{{ $ord->priority }}</span>
                        @endif
                    </td>
                    <td class="fs-13">
                        <div>{{ $ord->shipping_carrier ?? 'TBD' }}</div>
                        @if($ord->tracking_number)
                            <span class="badge badge-soft-secondary font-monospace">{{ $ord->tracking_number }}</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold">${{ number_format($ord->total_amount, 2) }}</td>
                    <td style="width: 140px;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: {{ $ord->progress_percent }}%"></div>
                            </div>
                            <span class="fs-11 text-muted">{{ $ord->progress_percent }}%</span>
                        </div>
                    </td>
                    <td>
                        @if($ord->status === 'shipped')
                            <span class="badge badge-soft-success"><i class="bi bi-truck me-1"></i> Shipped</span>
                        @elseif($ord->status === 'packed')
                            <span class="badge badge-soft-primary"><i class="bi bi-box me-1"></i> Packed</span>
                        @elseif($ord->status === 'picking')
                            <span class="badge badge-soft-info"><i class="bi bi-hand-index me-1"></i> Picking</span>
                        @else
                            <span class="badge badge-soft-warning"><i class="bi bi-clock me-1"></i> Confirmed</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            @if(!in_array($ord->status, ['shipped', 'delivered', 'cancelled']))
                            <a href="{{ route('outbound.pick-pack', $ord) }}" class="btn btn-sm btn-primary py-1 px-2 fs-12">
                                Pick &amp; Pack
                            </a>
                            @endif
                            <a href="{{ route('outbound.show', $ord) }}" class="btn btn-sm btn-outline-secondary py-1 px-2 fs-12">
                                Details
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">No outbound orders recorded yet.</td>
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
