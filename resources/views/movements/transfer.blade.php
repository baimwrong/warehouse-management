@extends('layouts.app')

@section('title', 'Internal Bin Transfer')

@section('content')
<div class="mb-4">
    <a href="{{ route('movements.index') }}" class="text-decoration-none fs-13 text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to Movements Ledger
    </a>
    <h4 class="fw-bold mt-2"><i class="bi bi-arrow-left-right text-primary me-2"></i> Internal Stock Relocation</h4>
    <p class="text-muted fs-14">Move goods between storage bins within the facility with transactional safety</p>
</div>

<div class="card card-wms" style="max-width: 800px;">
    <div class="card-body p-4">
        <form action="{{ route('movements.transfer.process') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="warehouse_id" class="form-label fs-13 fw-semibold">Warehouse Facility <span class="text-danger">*</span></label>
                <select name="warehouse_id" id="warehouse_id" class="form-select" required onchange="updateLocations(this.value)">
                    <option value="">-- Select Facility --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ old('warehouse_id', request('warehouse_id')) == $wh->id ? 'selected' : '' }}>
                            {{ $wh->name }} ({{ $wh->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="product_id" class="form-label fs-13 fw-semibold">Product SKU to Move <span class="text-danger">*</span></label>
                <select name="product_id" id="product_id" class="form-select" required>
                    <option value="">-- Select Product --</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}" {{ old('product_id', request('product_id')) == $prod->id ? 'selected' : '' }}>
                            {{ $prod->name }} [{{ $prod->sku }}] &bull; Total on-hand: {{ number_format($prod->total_quantity_on_hand) }} {{ $prod->uom }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="product_batch_id" class="form-label fs-13 fw-semibold">Batch / Lot Number (Optional)</label>
                <select name="product_batch_id" id="product_batch_id" class="form-select font-monospace">
                    <option value="">-- No Specific Batch / Standard Stock --</option>
                    @foreach($products as $prod)
                        @foreach($prod->batches as $batch)
                            <option value="{{ $batch->id }}" {{ old('product_batch_id') == $batch->id ? 'selected' : '' }}>
                                [{{ $prod->sku }}] Lot: {{ $batch->batch_number }} @if($batch->expiry_date)(Exp: {{ $batch->expiry_date->format('Y-m-d') }})@endif
                            </option>
                        @endforeach
                    @endforeach
                </select>
                <div class="fs-12 text-muted mt-1">Select the lot if transferring batch-tracked goods between bins.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="from_location_id" class="form-label fs-13 fw-semibold">Source Storage Bin (From) <span class="text-danger">*</span></label>
                    <select name="from_location_id" id="from_location_id" class="form-select font-monospace" required>
                        <option value="">-- Select Source Bin --</option>
                        @foreach($warehouses as $wh)
                            @foreach($wh->zones as $zn)
                                @foreach($zn->storageLocations as $loc)
                                    <option value="{{ $loc->id }}" data-warehouse="{{ $wh->id }}" class="loc-option" {{ old('from_location_id', request('from_location_id')) == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->location_code }} ({{ $zn->name }})
                                    </option>
                                @endforeach
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="to_location_id" class="form-label fs-13 fw-semibold">Destination Storage Bin (To) <span class="text-danger">*</span></label>
                    <select name="to_location_id" id="to_location_id" class="form-select font-monospace" required>
                        <option value="">-- Select Target Bin --</option>
                        @foreach($warehouses as $wh)
                            @foreach($wh->zones as $zn)
                                @foreach($zn->storageLocations as $loc)
                                    <option value="{{ $loc->id }}" data-warehouse="{{ $wh->id }}" class="loc-option" {{ old('to_location_id') == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->location_code }} ({{ $zn->name }})
                                    </option>
                                @endforeach
                            @endforeach
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="quantity" class="form-label fs-13 fw-semibold">Quantity to Move <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" min="0.0001" name="quantity" id="quantity" class="form-control" placeholder="e.g. 10" value="{{ old('quantity') }}" required>
                </div>
                <div class="col-md-6">
                    <label for="notes" class="form-label fs-13 fw-semibold">Reason / Movement Notes</label>
                    <input type="text" name="notes" id="notes" class="form-control" placeholder="e.g. Bin consolidation / restocking forward pick area" value="{{ old('notes') }}">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('movements.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-arrow-left-right me-1"></i> Execute Relocation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateLocations(whId) {
    const locs = document.querySelectorAll('.loc-option');
    locs.forEach(opt => {
        if (!whId || opt.getAttribute('data-warehouse') === whId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
}
document.addEventListener('DOMContentLoaded', () => {
    const wh = document.getElementById('warehouse_id');
    if (wh && wh.value) updateLocations(wh.value);
});
</script>
@endsection
