<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packing Slip &amp; BOL - {{ $order->order_number }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; }
        .document-sheet { max-width: 860px; margin: 30px auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .table-doc th { background-color: #f1f5f9; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .table-doc td { font-size: 13px; }
        .sig-line { border-bottom: 1.5px solid #000; min-height: 40px; margin-bottom: 6px; }
        @media print {
            body { background: #fff; margin: 0; padding: 0; }
            .document-sheet { box-shadow: none; margin: 0; padding: 15px; max-width: 100%; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container py-3 no-print text-center">
    <div class="d-inline-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="bi bi-printer me-1"></i> Print Packing Slip &amp; BOL
        </button>
        <a href="{{ route('outbound.show', $order) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Order
        </a>
    </div>
</div>

<div class="document-sheet">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-primary">{{ config('app.wms.company_name', 'WMS Enterprise Logistics') }}</h3>
            <p class="text-muted fs-12 mb-0">Warehouse Facility: {{ $order->warehouse->name }} ({{ $order->warehouse->code }})</p>
            <p class="text-muted fs-12 mb-0">{{ $order->warehouse->address }}, {{ $order->warehouse->city }}, {{ $order->warehouse->country }}</p>
        </div>
        <div class="text-end">
            <h5 class="fw-bold text-uppercase mb-1">Packing Slip &amp; BOL</h5>
            <div class="fs-13 font-monospace fw-semibold">{{ $order->order_number }}</div>
            <div class="mt-2 d-inline-block">
                {!! $barcodeSvg !!}
            </div>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="row g-4 mb-4">
        <div class="col-6">
            <div class="p-3 border rounded bg-light">
                <div class="fs-11 fw-bold text-uppercase text-muted mb-1">Customer / Ship-To:</div>
                <div class="fw-bold fs-14">{{ $order->customer->name }}</div>
                <div class="fs-12 text-muted">{{ $order->customer->shipping_address ?? $order->customer->address ?? 'Warehouse Pick-up' }}</div>
                <div class="fs-12 text-muted">{{ $order->customer->city }}, {{ $order->customer->country }}</div>
                <div class="fs-12 text-muted">Phone: {{ $order->customer->phone ?? 'N/A' }} | Email: {{ $order->customer->email ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="col-6">
            <div class="p-3 border rounded">
                <div class="row fs-12 g-2">
                    <div class="col-6 text-muted">Order Date:</div>
                    <div class="col-6 fw-semibold text-end">{{ $order->created_at->format('Y-m-d') }}</div>
                    <div class="col-6 text-muted">Shipped Date:</div>
                    <div class="col-6 fw-semibold text-end">{{ $order->shipped_date ? $order->shipped_date->format('Y-m-d H:i') : 'Pending' }}</div>
                    <div class="col-6 text-muted">Carrier:</div>
                    <div class="col-6 fw-semibold text-end">{{ $order->shipping_carrier ?? 'Internal Fleet' }}</div>
                    <div class="col-6 text-muted">Tracking #:</div>
                    <div class="col-6 fw-semibold text-end font-monospace">{{ $order->tracking_number ?? 'N/A' }}</div>
                    <div class="col-6 text-muted">Status:</div>
                    <div class="col-6 fw-semibold text-end text-capitalize">{{ str_replace('_', ' ', $order->status) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <table class="table table-bordered table-doc align-middle mb-4">
        <thead>
            <tr>
                <th style="width: 50px;" class="text-center">#</th>
                <th>Product SKU &amp; Description</th>
                <th class="text-center">Bin Location</th>
                <th class="text-center">Batch / Lot</th>
                <th class="text-end" style="width: 100px;">Ordered</th>
                <th class="text-end" style="width: 100px;">Packed</th>
                <th class="text-end" style="width: 110px;">Unit Price</th>
                <th class="text-end" style="width: 110px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx => $item)
            <tr>
                <td class="text-center text-muted fs-12">{{ $idx + 1 }}</td>
                <td>
                    <div class="fw-bold">{{ $item->product->name }}</div>
                    <div class="fs-11 font-monospace text-muted">SKU: {{ $item->product->sku }}</div>
                </td>
                <td class="text-center font-monospace fs-12">
                    {{ $item->sourceLocation?->location_code ?? '-' }}
                </td>
                <td class="text-center font-monospace fs-12">
                    {{ $item->batch?->batch_number ?? '-' }}
                </td>
                <td class="text-end fw-semibold">{{ number_format($item->quantity_ordered, 2) }} {{ $item->product->uom }}</td>
                <td class="text-end fw-bold text-success">{{ number_format($item->quantity_packed, 2) }} {{ $item->product->uom }}</td>
                <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                <td class="text-end fw-bold">${{ number_format($item->quantity_packed * $item->unit_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="text-end fw-bold">Total Declared Shipment Value:</td>
                <td class="text-end fw-bold fs-14 text-primary">${{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($order->notes)
    <div class="p-2 border rounded bg-light fs-12 mb-4">
        <strong>Dispatch Notes:</strong> {{ $order->notes }}
    </div>
    @endif

    <!-- Sign-off Block for Carriers & Receivers -->
    <div class="row pt-4 mt-4 border-top g-4">
        <div class="col-4">
            <div class="fs-12 fw-bold text-uppercase text-muted mb-2">Picked &amp; Packed By</div>
            <div class="sig-line"></div>
            <div class="fs-11 text-muted">{{ $order->creator->name ?? 'Warehouse Staff' }}</div>
            <div class="fs-11 text-muted">Date: ________________</div>
        </div>
        <div class="col-4">
            <div class="fs-12 fw-bold text-uppercase text-muted mb-2">Freight Driver / Carrier</div>
            <div class="sig-line"></div>
            <div class="fs-11 text-muted">Driver Name: ________________</div>
            <div class="fs-11 text-muted">Date: ________________</div>
        </div>
        <div class="col-4">
            <div class="fs-12 fw-bold text-uppercase text-muted mb-2">Customer Received In Good Condition</div>
            <div class="sig-line"></div>
            <div class="fs-11 text-muted">Signature &amp; Company Stamp</div>
            <div class="fs-11 text-muted">Date: ________________</div>
        </div>
    </div>
</div>

</body>
</html>
