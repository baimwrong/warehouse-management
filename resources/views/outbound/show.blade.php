@extends('layouts.app')

@section('title', $order->order_number)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <a href="{{ route('outbound.index') }}" class="text-decoration-none fs-13 text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to Outbound Orders
        </a>
        <h4 class="fw-bold mt-2 mb-0 font-monospace">{{ $order->order_number }}</h4>
        <div class="text-muted fs-13">
            Customer: <strong>{{ $order->customer->name ?? 'N/A' }}</strong> &bull;
            Facility: <strong>{{ $order->warehouse->name ?? 'N/A' }}</strong>
        </div>
    </div>
    @if(!in_array($order->status, ['shipped', 'delivered', 'cancelled']))
    <a href="{{ route('outbound.pick-pack', $order) }}" class="btn btn-primary">
        <i class="bi bi-box me-1"></i> Pick, Pack &amp; Dispatch Order
    </a>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <div class="card card-wms p-3">
            <div class="text-muted fs-12">Fulfillment Status</div>
            <div class="mt-1">
                @if($order->status === 'shipped')
                    <span class="badge badge-soft-success fs-13"><i class="bi bi-truck me-1"></i> Shipped</span>
                @elseif($order->status === 'packed')
                    <span class="badge badge-soft-primary fs-13"><i class="bi bi-box me-1"></i> Packed</span>
                @else
                    <span class="badge badge-soft-warning fs-13"><i class="bi bi-clock me-1"></i> Confirmed</span>
                @endif
            </div>
            @if($order->shipped_date)
                <div class="text-muted fs-11 mt-1">Dispatched on {{ $order->shipped_date->format('Y-m-d H:i') }}</div>
            @endif
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card card-wms p-3">
            <div class="text-muted fs-12">Total Sales Amount</div>
            <div class="fs-4 fw-bold mt-1 text-success">${{ number_format($order->total_amount, 2) }}</div>
            <div class="text-muted fs-11">Order value</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card card-wms p-3">
            <div class="text-muted fs-12">Carrier &amp; Tracking</div>
            <div class="fw-bold fs-14 mt-1">{{ $order->shipping_carrier ?? 'Internal Fleet' }}</div>
            <div class="text-muted fs-11 font-monospace">{{ $order->tracking_number ?? 'No tracking assigned' }}</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card card-wms p-3">
            <div class="text-muted fs-12">Scheduled Dispatch</div>
            <div class="fw-bold fs-14 mt-1">{{ $order->scheduled_ship_date ? $order->scheduled_ship_date->format('Y-m-d') : 'Standard' }}</div>
            <div class="text-muted fs-11">Ship by target</div>
        </div>
    </div>
</div>

@if($order->notes)
<div class="alert alert-light border mb-4 fs-13">
    <strong>Packing &amp; Dispatch Instructions:</strong> {{ $order->notes }}
</div>
@endif

<div class="card card-wms">
    <div class="card-header">
        <i class="bi bi-box-seam text-primary me-2"></i> Ordered SKUs &amp; Pick List
    </div>
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Product / SKU</th>
                    <th class="text-end">Ordered Qty</th>
                    <th class="text-end">Picked Qty</th>
                    <th class="text-end">Packed Qty</th>
                    <th class="text-end">Sales Price</th>
                    <th>Source Storage Bin</th>
                    <th>Fulfillment</th>
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
                    <td class="text-end fw-semibold">{{ number_format($item->quantity_ordered) }} {{ $item->product->uom }}</td>
                    <td class="text-end fw-bold">{{ number_format($item->quantity_picked) }}</td>
                    <td class="text-end fw-bold {{ $item->quantity_packed >= $item->quantity_ordered ? 'text-success' : 'text-warning' }}">
                        {{ number_format($item->quantity_packed) }}
                    </td>
                    <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                    <td>
                        @if($item->sourceLocation)
                            <span class="badge badge-soft-primary font-monospace">{{ $item->sourceLocation->location_code }}</span>
                        @else
                            <span class="text-muted fs-12">&mdash; Unassigned</span>
                        @endif
                    </td>
                    <td>
                        @if($item->status === 'shipped')
                            <span class="badge badge-soft-success">Shipped</span>
                        @elseif($item->status === 'packed')
                            <span class="badge badge-soft-primary">Packed</span>
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
