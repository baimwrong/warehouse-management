<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'WMS Enterprise') }}</title>

    <!-- 100% Offline Local Assets (No Internet Required) -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <aside class="wms-sidebar d-flex flex-column flex-shrink-0">
        <div class="wms-sidebar-brand d-flex align-items-center">
            <i class="bi bi-boxes text-primary me-2 fs-4"></i>
            <div>
                <span class="fw-bold tracking-tight text-white">WMS</span>
                <span class="badge bg-primary text-white fs-10 ms-1 px-1.5 py-0.5">v13</span>
                <div class="text-secondary fs-12 fw-normal">PostgreSQL 18 Edition</div>
            </div>
        </div>

        <div class="flex-grow-1 overflow-y-auto py-2">
            <div class="wms-nav-header">Core Operations</div>
            <a href="{{ route('dashboard') }}" class="wms-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            <a href="{{ route('inbound.index') }}" class="wms-nav-link {{ request()->routeIs('inbound.*') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-in-down"></i> Inbound Receiving
            </a>
            <a href="{{ route('outbound.index') }}" class="wms-nav-link {{ request()->routeIs('outbound.*') ? 'active' : '' }}">
                <i class="bi bi-box-arrow-up-right"></i> Outbound Dispatch
            </a>
            <a href="{{ route('movements.transfer') }}" class="wms-nav-link {{ request()->routeIs('movements.transfer') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i> Internal Bin Transfer
            </a>
            <a href="{{ route('adjustments.index') }}" class="wms-nav-link {{ request()->routeIs('adjustments.*') ? 'active' : '' }}">
                <i class="bi bi-sliders"></i> Stock Adjustments
            </a>

            <div class="wms-nav-header">Inventory & Master Data</div>
            <a href="{{ route('inventory.index') }}" class="wms-nav-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}">
                <i class="bi bi-layers"></i> Stock on Hand
            </a>
            <a href="{{ route('inventory.low-stock') }}" class="wms-nav-link {{ request()->routeIs('inventory.low-stock') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle"></i> Low Stock Alerts
            </a>
            <a href="{{ route('inventory.batches') }}" class="wms-nav-link {{ request()->routeIs('inventory.batches') ? 'active' : '' }}">
                <i class="bi bi-calendar-check"></i> Lot & Expiry Tracking
            </a>
            <a href="{{ route('products.index') }}" class="wms-nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Products Catalog
            </a>
            <a href="{{ route('warehouses.index') }}" class="wms-nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Warehouses
            </a>
            <a href="{{ route('locations.index') }}" class="wms-nav-link {{ request()->routeIs('locations.index') ? 'active' : '' }}">
                <i class="bi bi-geo-alt"></i> Storage Bins
            </a>
            <a href="{{ route('locations.visual-map') }}" class="wms-nav-link {{ request()->routeIs('locations.visual-map') ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i> 2D Rack Map
            </a>

            <div class="wms-nav-header">Partners & Traceability</div>
            <a href="{{ route('suppliers.index') }}" class="wms-nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Suppliers
            </a>
            <a href="{{ route('customers.index') }}" class="wms-nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i> Customers
            </a>
            <a href="{{ route('movements.index') }}" class="wms-nav-link {{ request()->routeIs('movements.index') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Audit Movement Ledger
            </a>

            <div class="wms-nav-header">Analytics & Administration</div>
            <a href="{{ route('reports.valuation') }}" class="wms-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i> Valuation & Reports
            </a>

            @if(auth()->check() && auth()->user()->isAdmin())
            <a href="{{ route('admin.import.index') }}" class="wms-nav-link {{ request()->routeIs('admin.import.*') ? 'active' : '' }} text-warning">
                <i class="bi bi-file-earmark-arrow-up"></i> Admin Bulk Import
            </a>
            @endif
        </div>

        <!-- Sidebar footer -->
        <div class="p-3 border-top border-secondary border-opacity-25 bg-dark bg-opacity-50">
            <div class="d-flex align-items-center">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="overflow-hidden me-auto">
                    <div class="text-white text-truncate fw-medium fs-14">{{ auth()->user()->name ?? 'Operator' }}</div>
                    <div class="badge bg-secondary text-capitalize fs-11 px-1.5 py-0.5">{{ auth()->user()->role ?? 'Staff' }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-link text-secondary p-0 text-decoration-none" title="Log Out">
                        <i class="bi bi-box-arrow-right fs-5"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content wrapper -->
    <div class="flex-grow-1 d-flex flex-column min-vh-100">
        <!-- Topbar -->
        <header class="wms-topbar">
            <div class="d-flex align-items-center">
                <span class="badge bg-success-subtle text-success border border-success-subtle me-3">
                    <i class="bi bi-shield-check me-1"></i> Offline LAN Ready
                </span>
                <span class="text-muted fs-13 d-none d-md-inline">
                    {{ config('app.wms.company_name', 'Global Logistics') }}
                </span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('products.barcodes') }}" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center">
                    <i class="bi bi-upc-scan me-1"></i> Barcode Print
                </a>
                <a href="{{ route('inbound.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center">
                    <i class="bi bi-plus-lg me-1"></i> New Inbound PO
                </a>
            </div>
        </header>

        <!-- Flash messages -->
        <div class="px-4 pt-3">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-x-circle-fill me-2"></i>
                <strong>Please address the following errors:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
        </div>

        <!-- Page body -->
        <main class="wms-content flex-grow-1">
            @yield('content')
        </main>

        <footer class="bg-white border-top py-3 px-4 text-muted fs-12 d-flex justify-content-between align-items-center">
            <div>&copy; 2026 WMS Enterprise. Built for Laravel 13 & PostgreSQL 18.</div>
            <div>Internal Network Mode &bull; Assets: Local Vendored Bootstrap 5.3 &amp; Icons</div>
        </footer>
    </div>
</div>

<!-- 100% Offline Local JS Bundle -->
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
