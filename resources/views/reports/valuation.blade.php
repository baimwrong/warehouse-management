@extends('layouts.app')

@section('title', 'Inventory Valuation Report')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bar-chart-line text-primary me-2"></i> Inventory Valuation Report</h4>
        <p class="text-muted fs-14 mb-0">Financial capitalization of physical stock on hand evaluated by standard cost and sales value</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="card card-wms px-3 py-1 bg-success bg-opacity-10 border-success">
            <span class="fs-11 text-muted text-uppercase fw-semibold">Net Portfolio Valuation</span>
            <span class="fs-5 fw-bold text-success">${{ number_format($totalValuation, 2) }}</span>
        </div>
    </div>
</div>

<div class="card card-wms mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.valuation') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="warehouse_id" class="form-select form-select-sm">
                    <option value="">-- All Warehouses (Global) --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">-- All Categories --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('reports.valuation') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Product / SKU</th>
                    <th>Category</th>
                    <th>Warehouse Hub</th>
                    <th class="text-end">On-Hand Qty</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end">Total Cost Valuation</th>
                    <th class="text-end">MSRP Potential Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                <tr>
                    <td>
                        <a href="{{ route('products.show', $rec->product_id) }}" class="fw-bold text-dark text-decoration-none">
                            {{ $rec->product_name }}
                        </a>
                        <div class="font-monospace text-primary fs-12">{{ $rec->sku }}</div>
                    </td>
                    <td><span class="badge badge-soft-secondary">{{ $rec->category_name ?? 'General' }}</span></td>
                    <td class="fw-semibold fs-13">{{ $rec->warehouse_name }}</td>
                    <td class="text-end fw-bold">{{ number_format($rec->total_qty) }} {{ $rec->uom }}</td>
                    <td class="text-end">${{ number_format($rec->unit_cost, 2) }}</td>
                    <td class="text-end fw-bold text-success fs-14">${{ number_format($rec->total_cost_value, 2) }}</td>
                    <td class="text-end fw-bold text-primary fs-14">${{ number_format($rec->total_sales_value, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No valuation records available.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $records->links() }}
    </div>
    @endif
</div>
@endsection
