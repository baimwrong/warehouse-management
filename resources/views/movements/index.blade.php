@extends('layouts.app')

@section('title', 'Audit Movement Ledger')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-journal-text text-primary me-2"></i> Audit Stock Movement Ledger</h4>
        <p class="text-muted fs-14 mb-0">Immutable transaction log tracking all increases, deductions, transfers, and adjustments</p>
    </div>
    <a href="{{ route('movements.transfer') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-arrow-left-right me-1"></i> New Bin Transfer
    </a>
</div>

<!-- Filters -->
<div class="card card-wms mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('movements.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">-- All Movement Types --</option>
                    <option value="inbound" {{ $type === 'inbound' ? 'selected' : '' }}>Inbound PO Receipt</option>
                    <option value="outbound" {{ $type === 'outbound' ? 'selected' : '' }}>Outbound Dispatch</option>
                    <option value="transfer" {{ $type === 'transfer' ? 'selected' : '' }}>Internal Bin Transfer</option>
                    <option value="adjustment" {{ $type === 'adjustment' ? 'selected' : '' }}>Cycle Count Adjustment</option>
                    <option value="initial_balance" {{ $type === 'initial_balance' ? 'selected' : '' }}>Initial Balance</option>
                    <option value="bulk_import" {{ $type === 'bulk_import' ? 'selected' : '' }}>Admin Bulk Import</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">-- All Products --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" placeholder="From" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" placeholder="To" value="{{ $dateTo }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary w-50">Filter</button>
                <a href="{{ route('movements.index') }}" class="btn btn-sm btn-outline-secondary w-50">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Reference / Action</th>
                    <th>Product Item</th>
                    <th class="text-end">Quantity</th>
                    <th>Source Bin</th>
                    <th>Destination Bin</th>
                    <th>Operator</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $mv)
                <tr>
                    <td class="text-nowrap fs-12 text-muted">
                        <div>{{ $mv->created_at->format('Y-m-d') }}</div>
                        <div class="fs-11">{{ $mv->created_at->format('H:i:s') }}</div>
                    </td>
                    <td>
                        @if(in_array($mv->reference_type, ['inbound', 'initial', 'bulk_import']))
                            <span class="badge badge-soft-success text-capitalize">{{ str_replace('_', ' ', $mv->reference_type) }}</span>
                        @elseif($mv->reference_type === 'outbound')
                            <span class="badge badge-soft-danger">Outbound</span>
                        @elseif($mv->reference_type === 'transfer')
                            <span class="badge badge-soft-primary">Bin Transfer</span>
                        @else
                            <span class="badge badge-soft-warning text-capitalize">{{ $mv->reference_type }}</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('products.show', $mv->product) }}" class="fw-semibold text-dark text-decoration-none">
                            {{ $mv->product->name ?? 'N/A' }}
                        </a>
                        <div class="text-muted fs-11 font-monospace">{{ $mv->product->sku ?? '' }}</div>
                    </td>
                    <td class="text-end fw-bold fs-14 {{ in_array($mv->reference_type, ['inbound', 'initial', 'bulk_import']) ? 'text-success' : ($mv->reference_type === 'outbound' ? 'text-danger' : 'text-primary') }}">
                        {{ in_array($mv->reference_type, ['inbound', 'initial', 'bulk_import']) ? '+' : ($mv->reference_type === 'outbound' ? '-' : '') }}{{ number_format($mv->quantity) }} {{ $mv->product->uom ?? '' }}
                    </td>
                    <td>
                        @if($mv->fromLocation)
                            <span class="badge badge-soft-secondary font-monospace">{{ $mv->fromLocation->location_code }}</span>
                            <div class="text-muted fs-11">{{ $mv->fromLocation->warehouse->name ?? '' }}</div>
                        @else
                            <span class="text-muted fs-12">&mdash; Exterior</span>
                        @endif
                    </td>
                    <td>
                        @if($mv->toLocation)
                            <span class="badge badge-soft-primary font-monospace">{{ $mv->toLocation->location_code }}</span>
                            <div class="text-muted fs-11">{{ $mv->toLocation->warehouse->name ?? '' }}</div>
                        @else
                            <span class="text-muted fs-12">&mdash; Dispatched</span>
                        @endif
                    </td>
                    <td class="fs-13">{{ $mv->user->name ?? 'System Automated' }}</td>
                    <td class="text-muted fs-12 text-truncate" style="max-width: 200px;" title="{{ $mv->notes }}">
                        {{ $mv->notes ?? '&mdash;' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No movements match your criteria.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $movements->links() }}
    </div>
    @endif
</div>
@endsection
