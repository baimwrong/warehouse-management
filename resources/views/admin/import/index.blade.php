@extends('layouts.app')

@section('title', 'Admin Bulk Data Import Center')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i> Admin Bulk Data Import Center</h4>
        <p class="text-muted fs-14 mb-0">High-volume transactional CSV ingestion with dry-run validation, atomic rollback, and audit logs</p>
    </div>
</div>

<!-- Template Download Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card card-wms h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-box-seam text-primary fs-3 me-2"></i>
                <div class="fw-bold">1. Products Catalog</div>
            </div>
            <p class="text-muted fs-12 mb-3">Upload SKUs, names, categories, barcodes, UOM, dimensions, safety levels and pricing.</p>
            <a href="{{ route('admin.import.template', 'products') }}" class="btn btn-sm btn-outline-primary mt-auto">
                <i class="bi bi-download me-1"></i> Download Products Template
            </a>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card card-wms h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-layers text-success fs-3 me-2"></i>
                <div class="fw-bold">2. Initial Stock Balances</div>
            </div>
            <p class="text-muted fs-12 mb-3">Ingest physical inventory counts per warehouse, bin location, batch number and cost.</p>
            <a href="{{ route('admin.import.template', 'inventory') }}" class="btn btn-sm btn-outline-success mt-auto">
                <i class="bi bi-download me-1"></i> Download Stock Template
            </a>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card card-wms h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-geo-alt text-warning fs-3 me-2"></i>
                <div class="fw-bold">3. Storage Bins &amp; Racks</div>
            </div>
            <p class="text-muted fs-12 mb-3">Bulk create warehouse racks, aisles, shelves, bins with capacity limits.</p>
            <a href="{{ route('admin.import.template', 'locations') }}" class="btn btn-sm btn-outline-warning mt-auto">
                <i class="bi bi-download me-1"></i> Download Bins Template
            </a>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card card-wms h-100 p-3">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-truck text-info fs-3 me-2"></i>
                <div class="fw-bold">4. Suppliers Directory</div>
            </div>
            <p class="text-muted fs-12 mb-3">Import approved vendor contacts, payment terms, tax IDs and addresses.</p>
            <a href="{{ route('admin.import.template', 'suppliers') }}" class="btn btn-sm btn-outline-info mt-auto">
                <i class="bi bi-download me-1"></i> Download Suppliers Template
            </a>
        </div>
    </div>
</div>

<!-- Upload & Execution Panel -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card card-wms h-100">
            <div class="card-header">
                <i class="bi bi-upload text-primary me-2"></i> Upload CSV Dataset
            </div>
            <div class="card-body">
                <form action="{{ route('admin.import.process') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="import_type" class="form-label fs-13 fw-semibold">Target Entity Type</label>
                        <select name="import_type" id="import_type" class="form-select" required>
                            <option value="products">Products Master Catalog</option>
                            <option value="inventory">Inventory Initial Stock Balances</option>
                            <option value="locations">Warehouse Storage Bins</option>
                            <option value="suppliers">Approved Suppliers</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="file" class="form-label fs-13 fw-semibold">CSV File Upload</label>
                        <input type="file" name="file" id="file" class="form-control" accept=".csv,text/csv,text/plain" required>
                        <div class="form-text fs-12">Must be comma-delimited or semicolon-delimited CSV UTF-8. Maximum file size: 10MB.</div>
                    </div>

                    <div class="mb-4 form-check form-switch bg-light p-3 rounded border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="dry_run" name="dry_run" value="1" checked>
                        <label class="form-check-label fw-semibold fs-13" for="dry_run">
                            Dry-Run Mode (Simulation only, validates rows without writing to DB)
                        </label>
                        <div class="text-muted fs-12 ms-4">Uncheck this toggle when you are ready to permanently commit records into PostgreSQL.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-play-circle me-1"></i> Run Bulk Import
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Instructions & Specs -->
    <div class="col-lg-6">
        <div class="card card-wms h-100">
            <div class="card-header">
                <i class="bi bi-info-circle text-info me-2"></i> CSV Format Specifications &amp; Rules
            </div>
            <div class="card-body fs-13">
                <h6 class="fw-bold mb-2">Import Pipeline Safety Features:</h6>
                <ul class="mb-3 ps-3 text-muted">
                    <li><strong>Transactional Rollback:</strong> In live mode, all operations are enclosed inside a PostgreSQL transaction. If unrecoverable errors occur, changes are rolled back cleanly.</li>
                    <li><strong>BOM Marker &amp; Delimiter Auto-Detection:</strong> Supports UTF-8 with or without BOM, comma (<code>,</code>) or semicolon (<code>;</code>) column separation.</li>
                    <li><strong>Automatic Reference Resolution:</strong> Missing categories or zones specified in your CSV are automatically created on the fly.</li>
                    <li><strong>PostgreSQL JSONB Audit:</strong> Every import attempt logs the exact line errors and payloads for post-mortem inspection.</li>
                </ul>

                <h6 class="fw-bold mb-2">Recommended Sequence for Fresh Deployments:</h6>
                <ol class="ps-3 text-muted mb-0">
                    <li>Import <strong>Warehouses &amp; Storage Bins</strong> first.</li>
                    <li>Import <strong>Approved Suppliers</strong>.</li>
                    <li>Import <strong>Products Catalog</strong> with SKUs and dimensions.</li>
                    <li>Import <strong>Inventory Initial Balances</strong> to load stocks into bins.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Dry Run or Import Feedback Results Box -->
@if(session('dry_run_result') || session('import_result'))
@php
    $res = session('dry_run_result') ?? session('import_result');
@endphp
<div class="card card-wms mb-4 border-{{ $res['success'] ? 'success' : 'warning' }}">
    <div class="card-header d-flex justify-content-between align-items-center bg-{{ $res['success'] ? 'success-subtle' : 'warning-subtle' }}">
        <span class="fw-bold">
            <i class="bi {{ $res['success'] ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning' }} me-2"></i>
            {{ $res['dry_run'] ? 'Dry-Run Simulation Report' : 'Live Import Execution Report' }}
        </span>
        <span class="badge bg-{{ $res['status'] === 'completed' ? 'success' : ($res['status'] === 'partial' ? 'warning' : 'danger') }} text-capitalize">
            {{ $res['status'] }}
        </span>
    </div>
    <div class="card-body">
        <div class="row text-center mb-3">
            <div class="col-4">
                <div class="fs-4 fw-bold">{{ $res['total_rows'] }}</div>
                <div class="text-muted fs-12">Total Processed Rows</div>
            </div>
            <div class="col-4">
                <div class="fs-4 fw-bold text-success">{{ $res['successful_rows'] }}</div>
                <div class="text-muted fs-12">Valid Rows</div>
            </div>
            <div class="col-4">
                <div class="fs-4 fw-bold text-danger">{{ $res['failed_rows'] }}</div>
                <div class="text-muted fs-12">Rows with Errors</div>
            </div>
        </div>

        @if(!empty($res['errors']))
        <div class="alert alert-danger py-2 mb-2 fs-13 fw-semibold">
            Row-by-Row Error Breakdown ({{ count($res['errors']) }} line items):
        </div>
        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
            <table class="table table-sm table-bordered fs-12 mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">CSV Line</th>
                        <th>Data Payload Preview</th>
                        <th>Validation Error Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($res['errors'] as $err)
                    <tr>
                        <td class="fw-bold text-center text-danger">{{ $err['line'] }}</td>
                        <td><code>{{ json_encode($err['data'] ?? [], JSON_UNESCAPED_SLASHES) }}</code></td>
                        <td class="text-danger">{{ implode('; ', $err['messages'] ?? []) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endif

<!-- Historical Import Audit Logs Table -->
<div class="card card-wms">
    <div class="card-header">
        <i class="bi bi-clock-history text-primary me-2"></i> Bulk Import Audit Trail (PostgreSQL JSONB Logs)
    </div>
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Entity Type</th>
                    <th>File Name</th>
                    <th class="text-end">Total Rows</th>
                    <th class="text-end">Success</th>
                    <th class="text-end">Failed</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-nowrap fs-13">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td><span class="badge badge-soft-primary text-uppercase">{{ $log->import_type }}</span></td>
                    <td class="fw-semibold fs-13">{{ $log->file_name }}</td>
                    <td class="text-end">{{ number_format($log->total_rows) }}</td>
                    <td class="text-end text-success fw-bold">{{ number_format($log->successful_rows) }}</td>
                    <td class="text-end text-danger fw-bold">{{ number_format($log->failed_rows) }}</td>
                    <td>
                        <span class="badge bg-{{ $log->status === 'completed' ? 'success' : ($log->status === 'partial' ? 'warning' : 'danger') }} text-capitalize">
                            {{ $log->status }}
                        </span>
                    </td>
                    <td>
                        @if(!empty($log->error_log))
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 fs-11" onclick="viewLogModal({{ $log->id }})">
                            <i class="bi bi-bug me-1"></i> Errors
                        </button>
                        @else
                        <span class="text-muted fs-12">&mdash;</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">No bulk imports have been executed yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $logs->links() }}
    </div>
    @endif
</div>

<!-- Modal for viewing detailed JSON errors -->
<div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-15 fw-bold" id="logModalLabel">Import Error Log Inspection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="modalErrorContent" class="bg-light p-3 rounded fs-12 mb-0" style="max-height: 400px; overflow-y: auto;"></pre>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function viewLogModal(logId) {
    fetch(`/admin/import/logs/${logId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalErrorContent').textContent = JSON.stringify(data.error_log, null, 2);
            new bootstrap.Modal(document.getElementById('logModal')).show();
        })
        .catch(err => alert('Failed to fetch log details'));
}
</script>
@endpush
@endsection
