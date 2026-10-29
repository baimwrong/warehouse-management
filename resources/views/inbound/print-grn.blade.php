<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goods Receipt Note (GRN) - {{ $order->order_number }}</title>
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
            <i class="bi bi-printer me-1"></i> Print Goods Receipt Note (GRN)
        </button>
        <a href="{{ route('inbound.show', $order) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to PO
        </a>
    </div>
</div>

<div class="document-sheet">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-primary">{{ config('app.wms.company_name', 'WMS Enterprise Logistics') }}</h3>
            <p class="text-muted fs-12 mb-0">Receiving Facility: {{ $order->warehouse->name }} ({{ $order->warehouse->code }})</p>
            <p class="text-muted fs-12 mb-0">{{ $order->warehouse->address }}, {{ $order->warehouse->city }}, {{ $order->warehouse->country }}</p>
        </div>
        <div class="text-end">
            <h5 class="fw-bold text-uppercase mb-1">Goods Receipt Note (GRN)</h5>
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
                <div class="fs-11 fw-bold text-uppercase text-muted mb-1">Delivered By / Vendor:</div>
                <div class="fw-bold fs-14">{{ $order->supplier->name }}</div>
                <div class="fs-12 text-muted">{{ $order->supplier->address ?? 'N/A' }}, {{ $order->supplier->city ?? '' }}</div>
                <div class="fs-12 text-muted">Contact: {{ $order->supplier->contact_person ?? 'N/A' }} | Phone: {{ $order->supplier->phone ?? 'N/A' }}</div>
                <div class="fs-12 text-muted">Vendor Code: {{ $order->supplier->code }}</div>
            </div>
        </div>
        <div class="col-6">
            <div class="p-3 border rounded">
                <div class="row fs-12 g-2">
                    <div class="col-6 text-muted">PO Issue Date:</div>
                    <div class="col-6 fw-semibold text-end">{{ $order->created_at->format('Y-m-d') }}</div>
                    <div class="col-6 text-muted">Received Date:</div>
                    <div class="col-6 fw-semibold text-end">{{ $order->received_date ? $order->received_date->format('Y-m-d H:i') : 'Pending' }}</div>
                    <div class="col-6 text-muted">Created By:</div>
                    <div class="col-6 fw-semibold text-end">{{ $order->creator->name ?? 'Staff' }}</div>
                    <div class="col-6 text-muted">Receiving Status:</div>
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
                <th class="text-center">Putaway Bin</th>
                <th class="text-center">Batch / Lot</th>
                <th class="text-center">Expiry Date</th>
                <th class="text-end" style="width: 90px;">Expected</th>
                <th class="text-end" style="width: 90px;">Received</th>
                <th class="text-end" style="width: 100px;">Variance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx => $item)
            @php $var = $item->quantity_received - $item->quantity_expected; @endphp
            <tr>
                <td class="text-center text-muted fs-12">{{ $idx + 1 }}</td>
                <td>
                    <div class="fw-bold">{{ $item->product->name }}</div>
                    <div class="fs-11 font-monospace text-muted">SKU: {{ $item->product->sku }}</div>
                </td>
                <td class="text-center font-monospace fs-12">
                    {{ $item->targetLocation?->location_code ?? '-' }}
                </td>
                <td class="text-center font-monospace fs-12">
                    {{ $item->batch_number ?? '-' }}
                </td>
                <td class="text-center fs-12">
                    {{ $item->expiry_date ? $item->expiry_date->format('Y-m-d') : 'N/A' }}
                </td>
                <td class="text-end fw-semibold">{{ number_format($item->quantity_expected, 2) }}</td>
                <td class="text-end fw-bold text-success">{{ number_format($item->quantity_received, 2) }}</td>
                <td class="text-end fw-bold {{ $var < 0 ? 'text-danger' : ($var > 0 ? 'text-warning' : 'text-muted') }}">
                    {{ $var > 0 ? '+' : '' }}{{ number_format($var, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($order->notes)
    <div class="p-2 border rounded bg-light fs-12 mb-4">
        <strong>Inbound Notes:</strong> {{ $order->notes }}
    </div>
    @endif

    <!-- Sign-off Block for Receiving Dock & Supervisor -->
    <div class="row pt-4 mt-4 border-top g-4">
        <div class="col-4">
            <div class="fs-12 fw-bold text-uppercase text-muted mb-2">Freight Delivery Driver</div>
            <div class="sig-line"></div>
            <div class="fs-11 text-muted">Carrier / Freight Line: ____________</div>
            <div class="fs-11 text-muted">Date: ________________</div>
        </div>
        <div class="col-4">
            <div class="fs-12 fw-bold text-uppercase text-muted mb-2">Receiving Dock Inspector</div>
            <div class="sig-line"></div>
            <div class="fs-11 text-muted">Name: ________________</div>
            <div class="fs-11 text-muted">Date: ________________</div>
        </div>
        <div class="col-4">
            <div class="fs-12 fw-bold text-uppercase text-muted mb-2">Warehouse Supervisor Approval</div>
            <div class="sig-line"></div>
            <div class="fs-11 text-muted">Signature &amp; Stamp: ________________</div>
            <div class="fs-11 text-muted">Date: ________________</div>
        </div>
    </div>
</div>

</body>
</html>
