@extends('layouts.app')

@section('title', 'Warehouse Operations Dashboard')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1">Operations Overview</h4>
        <p class="text-muted fs-14 mb-0">Real-time telemetry and warehouse throughput statistics</p>
    </div>

    <!-- Warehouse filter dropdown -->
    <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center gap-2">
        <label for="warehouse_id" class="fs-13 fw-semibold text-muted text-nowrap">Filter Warehouse:</label>
        <select name="warehouse_id" id="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">-- All Warehouses (Global View) --</option>
            @foreach($warehouses as $wh)
                <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>
                    {{ $wh->name }} ({{ $wh->code }})
                </option>
            @endforeach
        </select>
    </form>
</div>

<!-- KPI Metric Cards Grid -->
<div class="row g-3 mb-4">
    <!-- Total SKUs -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 text-uppercase fw-semibold">Active SKUs</div>
                    <div class="fs-4 fw-bold mt-1">{{ number_format($totalSkus) }}</div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-box-seam"></i>
                </div>
            </div>
            <div class="fs-12 text-muted mt-2">Catalog master</div>
        </div>
    </div>

    <!-- Total Stock On Hand -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 text-uppercase fw-semibold">On-Hand Units</div>
                    <div class="fs-4 fw-bold mt-1 text-primary">{{ number_format($totalItemsOnHand) }}</div>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-layers"></i>
                </div>
            </div>
            <div class="fs-12 text-muted mt-2">Physical inventory</div>
        </div>
    </div>

    <!-- Total Valuation -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 text-uppercase fw-semibold">Inventory Value</div>
                    <div class="fs-4 fw-bold mt-1 text-success">${{ number_format($totalValuation, 2) }}</div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-currency-dollar"></i>
                </div>
            </div>
            <div class="fs-12 text-muted mt-2">Cost valuation</div>
        </div>
    </div>

    <!-- Bin Utilization -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 text-uppercase fw-semibold">Bin Occupancy</div>
                    <div class="fs-4 fw-bold mt-1">{{ $binUtilization }}%</div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-pie-chart"></i>
                </div>
            </div>
            <div class="fs-12 text-muted mt-2">{{ $occupiedBins }} of {{ $totalBins }} bins occupied</div>
        </div>
    </div>

    <!-- Pending Inbound -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 text-uppercase fw-semibold">Pending Inbound</div>
                    <div class="fs-4 fw-bold mt-1 text-info">{{ $pendingInbound }}</div>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-box-arrow-in-down"></i>
                </div>
            </div>
            <div class="fs-12 text-muted mt-2">Awaiting putaway</div>
        </div>
    </div>

    <!-- Pending Outbound -->
    <div class="col-sm-6 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 text-uppercase fw-semibold">Pending Orders</div>
                    <div class="fs-4 fw-bold mt-1 text-danger">{{ $pendingOutbound }}</div>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-box-arrow-up-right"></i>
                </div>
            </div>
            <div class="fs-12 text-muted mt-2">To pick &amp; pack</div>
        </div>
    </div>
</div>

<!-- Middle Section: Chart & Operational Quick Links -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card card-wms h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up text-primary me-2"></i> 7-Day Inbound vs Outbound Throughput</span>
                <span class="badge bg-secondary-subtle text-secondary">Units moved</span>
            </div>
            <div class="card-body">
                <div style="height: 280px;">
                    <canvas id="throughputChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-wms h-100">
            <div class="card-header">
                <i class="bi bi-lightning-charge text-warning me-2"></i> Quick Workflow Actions
            </div>
            <div class="card-body d-flex flex-column justify-content-around gap-2">
                <a href="{{ route('inbound.create') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between p-3">
                    <div class="text-start">
                        <div class="fw-semibold">Create Purchase Order</div>
                        <div class="fs-12 text-muted">Receive inbound shipment from supplier</div>
                    </div>
                    <i class="bi bi-arrow-right-short fs-4"></i>
                </a>

                <a href="{{ route('outbound.create') }}" class="btn btn-outline-info d-flex align-items-center justify-content-between p-3">
                    <div class="text-start">
                        <div class="fw-semibold">Create Outbound Order</div>
                        <div class="fs-12 text-muted">Pick, pack and ship customer order</div>
                    </div>
                    <i class="bi bi-arrow-right-short fs-4"></i>
                </a>

                <a href="{{ route('movements.transfer') }}" class="btn btn-outline-secondary d-flex align-items-center justify-content-between p-3">
                    <div class="text-start">
                        <div class="fw-semibold">Bin-to-Bin Stock Transfer</div>
                        <div class="fs-12 text-muted">Relocate goods between warehouse locations</div>
                    </div>
                    <i class="bi bi-arrow-right-short fs-4"></i>
                </a>

                @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.import.index') }}" class="btn btn-outline-warning d-flex align-items-center justify-content-between p-3">
                    <div class="text-start">
                        <div class="fw-semibold">Admin Bulk Data Import</div>
                        <div class="fs-12 text-muted">Batch import products, stock, bins via CSV</div>
                    </div>
                    <i class="bi bi-file-earmark-arrow-up fs-4"></i>
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Bottom Section: Low Stock Warnings & Recent Ledger Feed -->
<div class="row g-4">
    <!-- Low Stock Alert -->
    <div class="col-lg-6">
        <div class="card card-wms h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle text-danger me-2"></i> Low Stock &amp; Reorder Warnings</span>
                <a href="{{ route('inventory.low-stock') }}" class="fs-12 text-decoration-none">View All ({{ $lowStockProducts->count() }})</a>
            </div>
            <div class="table-responsive">
                <table class="table table-wms align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product / SKU</th>
                            <th>Category</th>
                            <th class="text-end">On-Hand</th>
                            <th class="text-end">Reorder Pt</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lowStockProducts as $prod)
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $prod) }}" class="fw-semibold text-decoration-none">
                                    {{ $prod->name }}
                                </a>
                                <div class="text-muted fs-12">{{ $prod->sku }}</div>
                            </td>
                            <td><span class="badge badge-soft-secondary">{{ $prod->category->name ?? 'Uncategorized' }}</span></td>
                            <td class="text-end fw-bold {{ $prod->total_quantity_on_hand <= $prod->min_stock_level ? 'text-danger' : 'text-warning' }}">
                                {{ number_format($prod->total_quantity_on_hand) }} {{ $prod->uom }}
                            </td>
                            <td class="text-end text-muted">{{ number_format($prod->reorder_point) }}</td>
                            <td>
                                @if($prod->total_quantity_on_hand <= 0)
                                    <span class="badge badge-soft-danger">Out of Stock</span>
                                @elseif($prod->total_quantity_on_hand <= $prod->min_stock_level)
                                    <span class="badge badge-soft-danger">Critical Low</span>
                                @else
                                    <span class="badge badge-soft-warning">Reorder</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-check2-circle text-success fs-3 d-block mb-1"></i>
                                All products are well stocked above reorder thresholds!
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Audit Movements -->
    <div class="col-lg-6">
        <div class="card card-wms h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-text text-primary me-2"></i> Recent Stock Movement Ledger</span>
                <a href="{{ route('movements.index') }}" class="fs-12 text-decoration-none">Full Audit Trail</a>
            </div>
            <div class="table-responsive">
                <table class="table table-wms align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Item</th>
                            <th class="text-end">Qty</th>
                            <th>Locations</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentMovements as $mv)
                        <tr>
                            <td>
                                @if(in_array($mv->reference_type, ['inbound', 'initial', 'bulk_import']))
                                    <span class="badge badge-soft-success text-capitalize">{{ str_replace('_', ' ', $mv->reference_type) }}</span>
                                @elseif($mv->reference_type === 'outbound')
                                    <span class="badge badge-soft-danger">Outbound</span>
                                @elseif($mv->reference_type === 'transfer')
                                    <span class="badge badge-soft-primary">Transfer</span>
                                @else
                                    <span class="badge badge-soft-warning text-capitalize">{{ $mv->reference_type }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold fs-13 text-truncate" style="max-width: 140px;" title="{{ $mv->product->name ?? '' }}">
                                    {{ $mv->product->name ?? 'Deleted SKU' }}
                                </div>
                                <div class="text-muted fs-11">{{ $mv->product->sku ?? '' }}</div>
                            </td>
                            <td class="text-end fw-bold">
                                {{ in_array($mv->reference_type, ['inbound', 'initial', 'bulk_import']) ? '+' : '-' }}{{ number_format($mv->quantity) }}
                            </td>
                            <td class="fs-12">
                                @if($mv->fromLocation)
                                    <span class="text-muted">{{ $mv->fromLocation->location_code }}</span>
                                    <i class="bi bi-arrow-right mx-1"></i>
                                @endif
                                <span class="fw-semibold">{{ $mv->toLocation->location_code ?? 'Dispatched' }}</span>
                            </td>
                            <td class="text-muted fs-11 text-nowrap">
                                {{ $mv->created_at->diffForHumans() }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No movements recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('throughputChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($trendDates),
                datasets: [
                    {
                        label: 'Inbound Putaway Units',
                        data: @json($inboundCounts),
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                    },
                    {
                        label: 'Outbound Dispatched Units',
                        data: @json($outboundCounts),
                        backgroundColor: '#ef4444',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
