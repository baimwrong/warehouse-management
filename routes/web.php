<?php

use App\Http\Controllers\Admin\BulkImportController;
use App\Http\Controllers\Admin\UserController;
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

// Authentication Routes (with brute-force rate limiting)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

// Authenticated Application Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard (accessible by all authenticated staff)
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Storage Locations & 2D Visual Map (View: all staff; Create: manager/admin)
    Route::get('/locations/visual-map', [StorageLocationController::class, 'visualMap'])->name('locations.visual-map');
    Route::get('/locations', [StorageLocationController::class, 'index'])->name('locations.index');
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/locations/create', [StorageLocationController::class, 'create'])->name('locations.create');
        Route::post('/locations', [StorageLocationController::class, 'store'])->name('locations.store');
    });

    // Warehouses Management (View: all staff; Mutations: manager/admin)
    Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create');
        Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::get('/warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit');
        Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
    });
    Route::get('/warehouses/{warehouse}', [WarehouseController::class, 'show'])->name('warehouses.show');

    // Products Catalog & Barcodes (View: all staff; Mutations: manager/admin)
    Route::get('/products/barcodes', [ProductController::class, 'barcodes'])->name('products.barcodes');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    // Inventory & Batch Tracking
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
    Route::get('/inventory/batches', [InventoryController::class, 'batches'])->name('inventory.batches');

    // Stock Movements & Internal Bin Transfers
    Route::get('/movements', [StockMovementController::class, 'index'])->name('movements.index');
    Route::get('/movements/transfer', [StockMovementController::class, 'transferForm'])->name('movements.transfer');
    Route::post('/movements/transfer', [StockMovementController::class, 'processTransfer'])->name('movements.transfer.process');

    // Stock Adjustments & Cycle Counts (Request: all; Approval: manager/admin)
    Route::get('/adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
    Route::get('/adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
    Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
    Route::get('/adjustments/{adjustment}', [StockAdjustmentController::class, 'show'])->name('adjustments.show');
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/adjustments/{adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('adjustments.approve');
        Route::post('/adjustments/{adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('adjustments.reject');
    });

    // Inbound Receiving / Purchase Orders (View: all staff; Actions: manager, operator, admin)
    Route::get('/inbound', [InboundOrderController::class, 'index'])->name('inbound.index');
    Route::middleware('role:admin,manager,operator')->group(function () {
        Route::get('/inbound/create', [InboundOrderController::class, 'create'])->name('inbound.create');
        Route::post('/inbound', [InboundOrderController::class, 'store'])->name('inbound.store');
        Route::get('/inbound/{order}/receive', [InboundOrderController::class, 'receiveForm'])->name('inbound.receive');
        Route::post('/inbound/{order}/receive', [InboundOrderController::class, 'processReceipt'])->name('inbound.receive.process');
    });
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/inbound/{order}/cancel', [InboundOrderController::class, 'cancel'])->name('inbound.cancel');
    });
    Route::get('/inbound/{order}/print-grn', [InboundOrderController::class, 'printGrn'])->name('inbound.print-grn');
    Route::get('/inbound/{order}', [InboundOrderController::class, 'show'])->name('inbound.show');

    // Outbound Dispatch / Sales Orders (View: all staff; Actions: manager, dispatcher, admin)
    Route::get('/outbound', [OutboundOrderController::class, 'index'])->name('outbound.index');
    Route::middleware('role:admin,manager,dispatcher')->group(function () {
        Route::get('/outbound/create', [OutboundOrderController::class, 'create'])->name('outbound.create');
        Route::post('/outbound', [OutboundOrderController::class, 'store'])->name('outbound.store');
        Route::get('/outbound/{order}/pick-pack', [OutboundOrderController::class, 'pickPackForm'])->name('outbound.pick-pack');
        Route::post('/outbound/{order}/pick-pack', [OutboundOrderController::class, 'processPickPack'])->name('outbound.pick-pack.process');
        Route::post('/outbound/{order}/cancel', [OutboundOrderController::class, 'cancel'])->name('outbound.cancel');
    });
    Route::get('/outbound/{order}/print-pack-slip', [OutboundOrderController::class, 'printPackingSlip'])->name('outbound.print-slip');
    Route::get('/outbound/{order}', [OutboundOrderController::class, 'show'])->name('outbound.show');

    // Suppliers & Customers (View: all staff; Create/Edit: manager, admin)
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');

        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    });

    // Reports & Analytics (manager, admin)
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/reports/valuation', [ReportController::class, 'valuation'])->name('reports.valuation');
        Route::get('/reports/movements', [ReportController::class, 'movements'])->name('reports.movements');
        Route::get('/reports/utilization', [ReportController::class, 'utilization'])->name('reports.utilization');
    });

    // Admin Only: Administration & Bulk Data Import Center
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // Staff & User Management
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');

        // Bulk Data Import Center
        Route::get('/import', [BulkImportController::class, 'index'])->name('import.index');
        Route::post('/import', [BulkImportController::class, 'process'])->name('import.process');
        Route::get('/import/template/{type}', [BulkImportController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/import/logs/{log}', [BulkImportController::class, 'viewLog'])->name('import.log');
    });
});
