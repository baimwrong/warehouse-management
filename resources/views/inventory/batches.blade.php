@extends('layouts.app')

@section('title', 'Lot & Expiry Tracking')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <a href="{{ route('inventory.index') }}" class="text-decoration-none fs-13 text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to Inventory
        </a>
        <h4 class="fw-bold mt-2 mb-1"><i class="bi bi-calendar-check text-primary me-2"></i> Product Lot &amp; Expiry Date Tracking</h4>
        <p class="text-muted fs-14 mb-0">Expiration dates, manufacture dates, and shelf-life monitoring for perishable &amp; regulated goods</p>
    </div>
</div>

<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Batch / Lot Number</th>
                    <th>Product</th>
                    <th>Mfg Date</th>
                    <th>Expiry Date</th>
                    <th>Remaining Shelf Life</th>
                    <th>Storage Bins Allocated</th>
                    <th>Expiry Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                <tr>
                    <td>
                        <span class="fw-bold font-monospace text-dark">{{ $batch->batch_number }}</span>
                        @if($batch->supplier)
                            <div class="text-muted fs-11">Supplier: {{ $batch->supplier->name }}</div>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('products.show', $batch->product) }}" class="fw-semibold text-decoration-none text-dark">
                            {{ $batch->product->name }}
                        </a>
                        <div class="text-muted fs-11 font-monospace">{{ $batch->product->sku }}</div>
                    </td>
                    <td class="text-muted fs-13">{{ $batch->mfg_date ? $batch->mfg_date->format('Y-m-d') : 'N/A' }}</td>
                    <td class="fw-semibold fs-13">{{ $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : 'No Expiry' }}</td>
                    <td>
                        @if($batch->expiry_date)
                            @if($batch->is_expired)
                                <span class="text-danger fw-bold">Expired {{ abs($batch->days_until_expiration) }} days ago</span>
                            @else
                                <span class="{{ $batch->days_until_expiration < 60 ? 'text-warning fw-bold' : 'text-success' }}">
                                    {{ $batch->days_until_expiration }} days remaining
                                </span>
                            @endif
                        @else
                            <span class="text-muted">&infin; Non-perishable</span>
                        @endif
                    </td>
                    <td>
                        @foreach($batch->stocks->where('quantity_on_hand', '>', 0) as $stk)
                            <span class="badge badge-soft-secondary font-monospace">
                                {{ $stk->location->location_code ?? 'Bin' }}: {{ number_format($stk->quantity_on_hand) }}
                            </span>
                        @endforeach
                    </td>
                    <td>
                        @if($batch->is_expired)
                            <span class="badge badge-soft-danger"><i class="bi bi-x-circle me-1"></i> Expired</span>
                        @elseif($batch->days_until_expiration && $batch->days_until_expiration < 60)
                            <span class="badge badge-soft-warning"><i class="bi bi-exclamation-triangle me-1"></i> Expiring Soon</span>
                        @else
                            <span class="badge badge-soft-success"><i class="bi bi-check-circle me-1"></i> Good</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No product batches with expiry dates found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($batches->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $batches->links() }}
    </div>
    @endif
</div>
@endsection
