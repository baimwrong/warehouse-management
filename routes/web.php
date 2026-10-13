<?php

use App\Http\Controllers\Admin\BulkImportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InboundOrderController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OutboundOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StorageLocationController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Application Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Warehouses Management
    Route::resource('warehouses', WarehouseController::class);

    // Storage Locations & 2D Visual Map
    Route::get('/locations/visual-map', [StorageLocationController::class, 'visualMap'])->name('locations.visual-map');
    Route::get('/locations', [StorageLocationController::class, 'index'])->name('locations.index');
    Route::get('/locations/create', [StorageLocationController::class, 'create'])->name('locations.create');
    Route::post('/locations', [StorageLocationController::class, 'store'])->name('locations.store');

    // Products Catalog & Barcodes
    Route::get('/products/barcodes', [ProductController::class, 'barcodes'])->name('products.barcodes');
    Route::resource('products', ProductController::class);

    // Inventory & Batch Tracking
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
    Route::get('/inventory/batches', [InventoryController::class, 'batches'])->name('inventory.batches');

    // Stock Movements & Internal Bin Transfers
    Route::get('/movements', [StockMovementController::class, 'index'])->name('movements.index');
    Route::get('/movements/transfer', [StockMovementController::class, 'transferForm'])->name('movements.transfer');
    Route::post('/movements/transfer', [StockMovementController::class, 'processTransfer'])->name('movements.transfer.process');

    // Stock Adjustments & Cycle Counts
    Route::get('/adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
    Route::get('/adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
    Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
    Route::get('/adjustments/{adjustment}', [StockAdjustmentController::class, 'show'])->name('adjustments.show');
    Route::post('/adjustments/{adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('adjustments.approve');
    Route::post('/adjustments/{adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('adjustments.reject');

    // Inbound Receiving / Purchase Orders
    Route::get('/inbound', [InboundOrderController::class, 'index'])->name('inbound.index');
    Route::get('/inbound/create', [InboundOrderController::class, 'create'])->name('inbound.create');
    Route::post('/inbound', [InboundOrderController::class, 'store'])->name('inbound.store');
    Route::get('/inbound/{order}', [InboundOrderController::class, 'show'])->name('inbound.show');
    Route::get('/inbound/{order}/receive', [InboundOrderController::class, 'receiveForm'])->name('inbound.receive');
    Route::post('/inbound/{order}/receive', [InboundOrderController::class, 'processReceipt'])->name('inbound.receive.process');

    // Outbound Dispatch / Sales Orders
    Route::get('/outbound', [OutboundOrderController::class, 'index'])->name('outbound.index');
    Route::get('/outbound/create', [OutboundOrderController::class, 'create'])->name('outbound.create');
    Route::post('/outbound', [OutboundOrderController::class, 'store'])->name('outbound.store');
    Route::get('/outbound/{order}', [OutboundOrderController::class, 'show'])->name('outbound.show');
    Route::get('/outbound/{order}/pick-pack', [OutboundOrderController::class, 'pickPackForm'])->name('outbound.pick-pack');
    Route::post('/outbound/{order}/pick-pack', [OutboundOrderController::class, 'processPickPack'])->name('outbound.pick-pack.process');

    // Suppliers & Customers
    Route::resource('suppliers', SupplierController::class)->except(['show', 'destroy']);
    Route::resource('customers', CustomerController::class)->except(['show', 'destroy']);

    // Reports & Analytics
    Route::get('/reports/valuation', [ReportController::class, 'valuation'])->name('reports.valuation');
    Route::get('/reports/movements', [ReportController::class, 'movements'])->name('reports.movements');
    Route::get('/reports/utilization', [ReportController::class, 'utilization'])->name('reports.utilization');

    // Admin Only: Bulk Data Import Center
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/import', [BulkImportController::class, 'index'])->name('import.index');
        Route::post('/import', [BulkImportController::class, 'process'])->name('import.process');
        Route::get('/import/template/{type}', [BulkImportController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/import/logs/{log}', [BulkImportController::class, 'viewLog'])->name('import.log');
    });
});
