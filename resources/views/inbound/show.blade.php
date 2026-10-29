@extends('layouts.app')

@section('title', $order->order_number)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <a href="{{ route('inbound.index') }}" class="text-decoration-none fs-13 text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to Inbound Orders
        </a>
        <h4 class="fw-bold mt-2 mb-0 font-monospace">{{ $order->order_number }}</h4>
        <div class="text-muted fs-13">
            Supplier: <strong>{{ $order->supplier->name ?? 'N/A' }}</strong> &bull;
            Destination: <strong>{{ $order->warehouse->name ?? 'N/A' }}</strong>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inbound.print-grn', $order) }}" class="btn btn-outline-secondary" target="_blank">
            <i class="bi bi-printer me-1"></i> Print GRN
        </a>
        @if(in_array($order->status, ['pending_receipt', 'partially_received']))
        <a href="{{ route('inbound.receive', $order) }}" class="btn btn-primary">
            <i class="bi bi-box-arrow-in-down me-1"></i> Receive &amp; Putaway Items
        </a>
        @if(auth()->user()->isManager())
        <form action="{{ route('inbound.cancel', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this Purchase Order? Any unreceived lines will be cancelled.')">
            @csrf
            <button type="submit" class="btn btn-outline-danger">
                <i class="bi bi-x-circle me-1"></i> Cancel PO
            </button>
        </form>
        @endif
        @endif
    </div>
</div>

<!-- Details Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <div class="card card-wms p-3">
            <div class="text-muted fs-12">PO Status</div>
            <div class="mt-1">
                @if($order->status === 'received')
                    <span class="badge badge-soft-success fs-13"><i class="bi bi-check-circle-fill me-1"></i> Received</span>
                @elseif($order->status === 'partially_received')
                    <span class="badge badge-soft-info fs-13"><i class="bi bi-pie-chart-fill me-1"></i> Partial</span>
                @else
                    <span class="badge badge-soft-warning fs-13"><i class="bi bi-clock-fill me-1"></i> Pending Receipt</span>
                @endif
            </div>
            @if($order->received_date)
                <div class="text-muted fs-11 mt-1">Received on {{ $order->received_date->format('Y-m-d H:i') }}</div>
            @endif
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card card-wms p-3">
            <div class="text-muted fs-12">Total Valuation</div>
            <div class="fs-4 fw-bold mt-1 text-success">${{ number_format($order->total_amount, 2) }}</div>
            <div class="text-muted fs-11">Purchase cost</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card card-wms p-3">
            <div class="text-muted fs-12">Expected Date</div>
            <div class="fw-bold fs-14 mt-1">{{ $order->expected_date ? $order->expected_date->format('Y-m-d') : 'Immediate' }}</div>
            <div class="text-muted fs-11">Delivery target</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card card-wms p-3">
            <div class="text-muted fs-12">Created By</div>
            <div class="fw-bold fs-14 mt-1">{{ $order->creator->name ?? 'Staff' }}</div>
            <div class="text-muted fs-11">{{ $order->created_at->format('Y-m-d H:i') }}</div>
        </div>
    </div>
</div>

@if($order->notes)
<div class="alert alert-light border mb-4 fs-13">
    <strong>Delivery Notes:</strong> {{ $order->notes }}
</div>
@endif

<!-- Order Items Table -->
<div class="card card-wms">
    <div class="card-header">
        <i class="bi bi-box-seam text-primary me-2"></i> Line Items &amp; Putaway Storage Location
    </div>
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Product / SKU</th>
                    <th class="text-end">Expected Qty</th>
                    <th class="text-end">Received Qty</th>
                    <th class="text-end">Unit Cost</th>
                    <th>Assigned Storage Bin</th>
                    <th>Batch Lot #</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        <a href="{{ route('products.show', $item->product) }}" class="fw-bold text-dark text-decoration-none">
                            {{ $item->product->name }}
                        </a>
                        <div class="font-monospace text-primary fs-12">{{ $item->product->sku }}</div>
                    </td>
                    <td class="text-end fw-semibold">{{ number_format($item->quantity_expected) }} {{ $item->product->uom }}</td>
                    <td class="text-end fw-bold {{ $item->quantity_received >= $item->quantity_expected ? 'text-success' : 'text-warning' }}">
                        {{ number_format($item->quantity_received) }} {{ $item->product->uom }}
                    </td>
                    <td class="text-end">${{ number_format($item->unit_cost, 2) }}</td>
                    <td>
                        @if($item->targetLocation)
                            <span class="badge badge-soft-primary font-monospace">{{ $item->targetLocation->location_code }}</span>
                        @else
                            <span class="text-muted fs-12">&mdash; Unassigned</span>
                        @endif
                    </td>
                    <td>
                        @if($item->batch_number)
                            <span class="badge badge-soft-secondary font-monospace">{{ $item->batch_number }}</span>
                        @else
                            <span class="text-muted fs-12">&mdash;</span>
                        @endif
                    </td>
                    <td>
                        @if($item->status === 'received')
                            <span class="badge badge-soft-success">Received</span>
                        @elseif($item->status === 'partial')
                            <span class="badge badge-soft-info">Partial</span>
                        @else
                            <span class="badge badge-soft-warning">Pending</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
