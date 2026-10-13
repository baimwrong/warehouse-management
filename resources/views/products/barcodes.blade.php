@extends('layouts.app')

@section('title', 'Print Product Barcodes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <a href="{{ route('products.index') }}" class="text-decoration-none fs-13 text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to Products
        </a>
        <h4 class="fw-bold mt-2 mb-0">Barcode Label Printing Sheet</h4>
        <p class="text-muted fs-14 mb-0">Offline vector SVG barcode labels for bin &amp; item packaging</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Print Labels
    </button>
</div>

<div class="row g-3">
    @foreach($labels as $item)
    <div class="col-sm-6 col-md-4 col-xl-3">
        <div class="card card-wms p-3 text-center h-100 border">
            <div class="fw-bold fs-13 text-truncate mb-1" title="{{ $item['product']->name }}">
                {{ $item['product']->name }}
            </div>
            <div class="text-muted fs-11 font-monospace mb-2">
                SKU: {{ $item['product']->sku }}
            </div>
            <div class="my-auto d-flex justify-content-center">
                {!! $item['barcodeSvg'] !!}
            </div>
            <div class="mt-2 text-muted fs-11 border-top pt-2 d-flex justify-content-between">
                <span>{{ $item['product']->category->name ?? 'General' }}</span>
                <span>${{ number_format($item['product']->unit_cost, 2) }}</span>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
