@extends('layouts.app')

@section('title', 'Receive Inbound ' . $order->order_number)

@section('content')
<div class="mb-4">
    <a href="{{ route('inbound.show', $order) }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to {{ $order->order_number }}
    </a>
    <h4 class="fw-bold mt-2"><i class="bi bi-box-arrow-in-down text-primary me-2"></i> Receive &amp; Putaway Inbound Shipment</h4>
    <p class="text-muted fs-14">Verify received quantities against bill of lading, assign target storage bins, and register batch lots</p>
</div>

<form action="{{ route('inbound.receive.process', $order) }}" method="POST">
    @csrf

    <div class="card card-wms mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Receiving Inspection &amp; Putaway Assignment</span>
            <span class="badge bg-primary">Facility: {{ $order->warehouse->name }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-wms align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 280px;">Product SKU</th>
                            <th class="text-end" style="width: 120px;">Expected</th>
                            <th class="text-end" style="width: 140px;">Received Now</th>
                            <th style="width: 240px;">Target Storage Bin</th>
                            <th>Batch / Lot &amp; Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $index => $item)
                        @php
                            $remaining = max(0, $item->quantity_expected - $item->quantity_received);
                            $suggested = $recommendations[$item->id] ?? collect();
                        @endphp
                        <tr>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                                <div class="fw-bold">{{ $item->product->name }}</div>
                                <div class="font-monospace text-primary fs-12">{{ $item->product->sku }}</div>
                                <div class="text-muted fs-11">Already received: {{ number_format($item->quantity_received) }} / {{ number_format($item->quantity_expected) }}</div>
                            </td>
                            <td class="text-end fw-semibold">
                                {{ number_format($item->quantity_expected) }} {{ $item->product->uom }}
                            </td>
                            <td class="text-end">
                                <input type="number" step="0.0001" min="0" max="{{ $remaining }}" 
                                       name="items[{{ $index }}][received_qty]" 
                                       class="form-control form-control-sm text-end fw-bold" 
                                       value="{{ $remaining }}" required>
                            </td>
                            <td>
                                <select name="items[{{ $index }}][target_location_id]" class="form-select form-select-sm font-monospace" required>
                                    <option value="">-- Choose Storage Bin --</option>
                                    @if($suggested->isNotEmpty())
                                        <optgroup label="✨ Recommended Optimal Bins">
                                            @foreach($suggested as $sBin)
                                                <option value="{{ $sBin->id }}">
                                                    {{ $sBin->location_code }} ({{ $sBin->zone->name }}) - {{ $sBin->is_occupied ? 'Partial' : 'Vacant' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    <optgroup label="All Facility Bins">
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" {{ $item->target_location_id == $loc->id ? 'selected' : '' }}>
                                                {{ $loc->location_code }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                </select>
                            </td>
                            <td>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="text" name="items[{{ $index }}][batch_number]" 
                                               class="form-control form-control-sm font-monospace text-uppercase" 
                                               placeholder="Lot/Batch #" value="{{ old("items.{$index}.batch_number", 'LOT-' . date('Ymd')) }}">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" name="items[{{ $index }}][expiry_date]" 
                                               class="form-control form-control-sm" 
                                               title="Expiration Date" value="{{ old("items.{$index}.expiry_date") }}">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('inbound.show', $order) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check2-circle me-1"></i> Confirm Putaway &amp; Commit Stock
        </button>
    </div>
</form>
@endsection
