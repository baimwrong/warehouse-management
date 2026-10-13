# WMS Enterprise — Warehouse Management System

> **Production-Ready Enterprise Warehouse Management System (WMS)** built with **Laravel 13**, **PostgreSQL 18**, and **Bootstrap 5.3**. Designed for high-reliability, air-gapped internal LAN networks with **100% offline local asset vendoring** and a specialized **Admin Bulk Data Import Center**.

---

## 📋 Table of Contents
1. [System Architecture & Core Highlights](#system-architecture--core-highlights)
2. [Technology Stack](#technology-stack)
3. [Zero-Dependency Offline Assets (Air-Gapped Ready)](#zero-dependency-offline-assets-air-gapped-ready)
4. [Quick Start via Docker (Recommended for Host Machine)](#quick-start-via-docker-recommended-for-host-machine)
5. [Native Installation (Production Server Setup)](#native-installation-production-server-setup)
6. [Database Schema & PostgreSQL 18 Architecture](#database-schema--postgresql-18-architecture)
7. [Default Demo Credentials & RBAC Roles](#default-demo-credentials--rbac-roles)
8. [Admin Bulk Data Import System](#admin-bulk-data-import-system)
   - [Supported CSV Data Formats & Templates](#supported-csv-data-formats--templates)
   - [Dry-Run Validation & Rollback Guarantees](#dry-run-validation--rollback-guarantees)
   - [PostgreSQL JSONB Audit Trail](#postgresql-jsonb-audit-trail)
9. [Operational Core Modules](#operational-core-modules)
   - [Multi-Facility & 2D Visual Rack Map](#multi-facility--2d-visual-rack-map)
   - [Inbound Receiving & Smart Putaway](#inbound-receiving--smart-putaway)
   - [Outbound Order Fulfillment (Pick, Pack & Dispatch)](#outbound-order-fulfillment-pick-pack--dispatch)
   - [Immutable Stock Movement Ledger & Internal Transfers](#immutable-stock-movement-ledger--internal-transfers)
   - [Cycle Counts & Discrepancy Adjustments](#cycle-counts--discrepancy-adjustments)
   - [Offline SVG Barcode Label Generator](#offline-svg-barcode-label-generator)
10. [Testing & Verification](#testing--verification)

---

## 🏗 System Architecture & Core Highlights

WMS Enterprise is engineered for high-throughput distribution hubs, manufacturing staging floors, and multi-zone logistics facilities.

```
┌────────────────────────────────────────────────────────────────────────┐
│                          WMS Client Interface                          │
│  (100% Local Bootstrap 5.3.3 + Bootstrap Icons 1.11.3 + Chart.js 4.4.7) │
└────────────────────────────────────┬───────────────────────────────────┘
                                     │ HTTP / REST
┌────────────────────────────────────▼───────────────────────────────────┐
│                       Laravel 13 Application Layer                      │
│                                                                        │
│  ┌──────────────────────┐  ┌─────────────────────┐  ┌───────────────┐  │
│  │  Inbound & Putaway   │  │ Outbound Pick/Pack  │  │ Stock Ledger  │  │
│  └──────────┬───────────┘  └──────────┬──────────┘  └───────┬───────┘  │
│             │                         │                     │          │
│  ┌──────────▼─────────────────────────▼─────────────────────▼───────┐  │
│  │        Domain Services (InventoryService, BulkImportService)     │  │
│  └────────────────────────────────────┬─────────────────────────────┘  │
└───────────────────────────────────────┼────────────────────────────────┘
                                        │ PostgreSQL Driver
┌───────────────────────────────────────▼────────────────────────────────┐
│                       PostgreSQL 18 Enterprise Database                │
│    - JSONB Audit Logs          - Compound BTREE Indexes                │
│    - Foreign Key Constraints   - High-Precision Decimal Balance Types  │
└────────────────────────────────────────────────────────────────────────┘
```

- **Domain-Driven Architecture**: Critical logic is cleanly abstracted into domain services (`InventoryService`, `BulkImportService`, `PutawayService`, `BarcodeService`).
- **Atomic Concurrency Safety**: Stock mutations use PostgreSQL `SELECT ... FOR UPDATE` row locks inside `DB::transaction()` to avoid race conditions during simultaneous picking or putaway tasks.
- **Double-Entry Ledger Integrity**: Every stock fluctuation writes an immutable entry into `stock_movements`, ensuring 100% compliance and traceability.

---

## 🛠 Technology Stack

| Layer | Component | Version / Specification |
|---|---|---|
| **Framework** | Laravel Framework | **13.x / 12.x** (PHP `^8.3 || ^8.4`) |
| **Database** | PostgreSQL | **18.x** (with JSONB, Schema Search Path & SSL mode) |
| **Styling** | Bootstrap | **5.3.3** (Minified, Downloaded to `public/vendor/`) |
| **Icons** | Bootstrap Icons | **1.11.3** (WOFF2/WOFF Fonts in `public/vendor/`) |
| **Charts** | Chart.js | **4.4.7** (Standalone UMD in `public/vendor/`) |
| **Barcodes** | Native PHP SVG Engine | 100% Offline Code 128 / Barcode Generator |
| **Container** | Docker & Compose | PHP 8.4-fpm, PostgreSQL 18-alpine, Nginx, Redis |

---

## 📦 Zero-Dependency Offline Assets (Air-Gapped Ready)

Per your strict internal network requirement, **no external CDN requests are made**. All static assets are vendored directly in the project folder:

```
public/
├── vendor/
│   ├── bootstrap/
│   │   ├── css/bootstrap.min.css         (232 KB)
│   │   └── js/bootstrap.bundle.min.js    (80 KB)
│   ├── bootstrap-icons/
│   │   ├── bootstrap-icons.min.css       (85 KB)
│   │   └── fonts/
│   │       ├── bootstrap-icons.woff2     (130 KB)
│   │       └── bootstrap-icons.woff      (176 KB)
│   └── chartjs/
│       └── chart.umd.min.js              (205 KB)
├── css/
│   └── app.css                           (Modern enterprise layout & 2D rack map)
└── js/
    └── app.js                            (Dynamic row builders & calculators)
```

The application runs reliably on completely isolated Intranets, air-gapped warehouses, or factory subnets without WAN/Internet access.

---

## 🐳 Quick Start via Docker (Recommended for Host Machine)

Because your local development machine is running an older PHP version (e.g. PHP 8.2 in XAMPP for work-related projects), **Docker allows you to run this application on PHP 8.4 + PostgreSQL 18 instantly with zero conflicts on your host OS!**

### Step 1: Clone or Navigate to the Project Folder
```powershell
cd c:\xampp\htdocs\warehouse-management
```

### Step 2: Launch Docker Compose Containers
```powershell
docker compose up -d --build
```
This starts 4 dedicated containers:
- `wms_app`: PHP 8.4-fpm with `pdo_pgsql`, `pgsql`, `opcache`, `gd`, `bcmath`, `redis`
- `wms_postgres`: PostgreSQL 18 container with persistent volume
- `wms_web`: Nginx 1.25+ web server listening on port `8080`
- `wms_redis`: Redis Alpine caching & queue backend

### Step 3: Run Database Migrations and Seed Sample Data
```powershell
docker compose exec app php artisan migrate --seed
```

### Step 4: Open in Web Browser
Navigate to:
```
http://localhost:8080
```
*(Log in using any of the pre-seeded credentials listed below).*

---

## 🖥 Native Installation (Production Server Setup)

When deploying directly to a Linux or Windows Server with **PHP 8.3 / 8.4** and **PostgreSQL 18** installed natively:

1. **Copy Environment File**:
   ```bash
   cp .env.example .env
   ```

2. **Configure PostgreSQL 18 in `.env`**:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=warehouse_db
   DB_USERNAME=postgres
   DB_PASSWORD=your_secure_password
   DB_SCHEMA=public
   DB_SSLMODE=prefer
   ```

3. **Install Dependencies**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```

5. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate --seed
   ```

6. **Serve the Application**:
   - With Nginx / Apache: Point DocumentRoot to `c:\xampp\htdocs\warehouse-management\public`
   - Or with artisan CLI: `php artisan serve --port=8080`

---

## 🗄 Database Schema & PostgreSQL 18 Architecture

The database is built with clean 3rd Normal Form (3NF) relational design, explicit foreign keys, indexes, and PostgreSQL 18 optimizations:

```
[warehouses] ──< [warehouse_zones] ──< [storage_locations]
     │                                         │
     │                                         ▼
     │                                [inventory_stocks] >── [products] >── [categories]
     │                                         ▲                   │
     │                                         │                   ▼
     └──< [inbound_orders] >── [inbound_items] ┘            [product_batches]
     │
     └──< [outbound_orders] >── [outbound_items]
     │
     └──< [stock_adjustments] >── [stock_adjustment_items]
     │
     └──< [stock_movements] (Immutable Audit Ledger)
     │
     └──< [bulk_import_logs] (JSONB row tracing)
```

### Key Tables Overview:
1. `warehouses`: Distribution centers with volume capacity calculations in cubic meters (`capacity_cbm`).
2. `warehouse_zones`: Functional warehouse sections (`receiving`, `storage`, `cold_storage`, `packing`, `dispatch`).
3. `storage_locations`: 6-coordinate bins (`location_code`, `aisle`, `rack`, `shelf`, `bin`, `max_weight_kg`, `max_volume_cbm`, `is_occupied`).
4. `categories` & `products`: SKUs, barcodes, UOMs, weight, volume, safety stock, and reorder thresholds.
5. `product_batches`: Lot tracking with manufacture and expiry dates.
6. `inventory_stocks`: Real-time stock levels (`quantity_on_hand`, `quantity_reserved`) at each bin.
7. `stock_movements`: Immutable double-entry ledger tracking every unit added, removed, or transferred.
8. `inbound_orders` & `outbound_orders`: Purchase orders (receiving) and sales orders (picking, packing, shipping).
9. `stock_adjustments`: Manager-approved cycle count reconciliations and write-offs.
10. `bulk_import_logs`: Audit trail for admin imports, storing line-by-line errors in **PostgreSQL JSONB**.

---

## 👥 Default Demo Credentials & RBAC Roles

The system includes pre-configured accounts for all enterprise warehouse roles:

| Role | Email | Password | Scope & Permissions |
|---|---|---|---|
| **Super Admin** | `admin@wms.internal` | `password` | Full unrestricted access, **Admin Bulk Import Center**, user management, facility settings |
| **Warehouse Manager** | `manager@wms.internal` | `password` | Facility operations, stock adjustment approval/rejection, inventory reports, purchase orders |
| **Inventory Operator** | `operator@wms.internal` | `password` | Physical stock receiving, putaway, internal bin-to-bin relocation, cycle counting |
| **Lead Dispatcher** | `dispatcher@wms.internal` | `password` | Sales order picking, packing verification, bill of lading, carrier tracking & dispatch |

> **Pro-Tip**: The login page includes convenient **1-Click Demo Buttons** to populate credentials instantly.

---

## 📤 Admin Bulk Data Import System

The application features a dedicated **Admin Bulk Data Import Center** accessible at `/admin/import` for Administrators.

### Key Capabilities:
- **Transactional Atomicity**: Imports run inside a PostgreSQL transaction (`DB::beginTransaction()`). If fatal errors occur, changes are safely rolled back without leaving orphaned rows.
- **Dry-Run Simulation**: Toggle the switch to validate your CSV file without modifying any database records.
- **BOM & Delimiter Auto-Detection**: Handles both UTF-8 with BOM and standard UTF-8; automatically detects commas (`,`) and semicolons (`;`).
- **Dynamic Entity Resolution**: Missing categories or warehouse zones referenced in your CSV are created on the fly.
- **Downloadable Pristine Templates**: Click any of the 4 template download buttons to obtain pre-formatted CSV samples.

### Pre-Packaged Templates Location:
Templates are pre-generated and stored directly on disk at:
- `storage/app/templates/products_sample.csv`
- `storage/app/templates/inventory_sample.csv`
- `storage/app/templates/locations_sample.csv`
- `storage/app/templates/suppliers_sample.csv`

### Supported Formats & Fields:

#### 1. Products Catalog (`products`)
```csv
sku,barcode,name,description,category_code,uom,unit_cost,unit_price,min_stock_level,max_stock_level,reorder_point,weight_kg,volume_cbm
ELEC-DRN-001,893521094001,Pro Surveillance Drone,Industrial camera drone,ELECTRONICS,PCS,350.00,599.99,10,100,20,1.250,0.0150
TOOL-WRN-101,893521094003,Industrial Ratchet Wrench Set 24pc,Chrome vanadium steel,TOOLS,BOX,45.00,89.50,15,80,25,3.200,0.0220
```

#### 2. Initial Stock Balances (`inventory`)
```csv
warehouse_code,location_code,sku,quantity,batch_number,unit_cost,notes
WH-CENTRAL,WH01-ZN-A-R01-S01-B01,ELEC-DRN-001,25,BATCH-2026-A1,350.00,Initial stock balance
WH-CENTRAL,WH01-ZN-B-R02-S01-B01,TOOL-WRN-101,40,BATCH-2026-C1,45.00,Initial stock balance
```

#### 3. Storage Bins & Locations (`locations`)
```csv
warehouse_code,zone_code,location_code,aisle,rack,shelf,bin,max_weight_kg,max_volume_cbm
WH-CENTRAL,ZN-A,WH01-ZN-A-R01-S01-B01,A01,R01,S01,B01,1500.00,3.00
WH-CENTRAL,ZN-B,WH01-ZN-B-R02-S01-B01,B02,R02,S01,B01,2000.00,4.50
```

#### 4. Approved Suppliers (`suppliers`)
```csv
code,name,contact_person,email,phone,address,city,country,tax_id,payment_terms
SUPP-TECH-01,Global Tech Components Ltd,Marcus Vance,sales@globaltech.com,+1-555-019-2834,104 Industrial Way,San Jose,United States,US-9481726,Net 30
```

---

## 🏭 Operational Core Modules

### 1. 2D Visual Rack & Bin Map
Navigate to `/locations/visual-map` to view an interactive 2D floor plan of your facility:
- **Green Bins**: Vacant storage locations with available volume and weight capacity.
- **Red Bins**: Occupied locations.
- **Interactive Tooltip**: Hover or tap any bin to inspect coordinates, stored SKUs, on-hand counts, and weight limits.

### 2. Inbound Receiving & Smart Putaway
When goods arrive at the dock:
1. Open the Purchase Order and click **Receive & Putaway Items**.
2. Enter the actual received quantity from the freight carrier.
3. The system's **Putaway Engine** (`PutawayService`) recommends the optimal vacant storage bins in the warehouse based on product weight and volume dimensions.
4. Input the Lot/Batch number and expiration date to initialize shelf-life tracking.
5. On submission, inventory balances are atomically incremented and an inbound ledger entry is created.

### 3. Outbound Order Fulfillment (Pick, Pack & Dispatch)
For sales orders:
1. Navigate to `/outbound` and review pending orders ordered by priority (**Urgent / High / Medium / Low**).
2. Open **Pick & Pack**. The system displays all storage bins holding stock for the requested SKUs.
3. Select the source bin and verify the picked quantity.
4. Add the carrier tracking number (e.g., DHL / FedEx / UPS).
5. Click **Confirm Dispatch & Deduct Stock** to commit the physical stock deduction and log the audit movement.

### 4. Immutable Stock Movement Ledger & Internal Relocation
Navigate to `/movements` to view the master material ledger:
- Filters by **Movement Type** (`inbound`, `outbound`, `transfer`, `adjustment`, `bulk_import`), **Product**, or **Date Range**.
- Need to move goods between racks? Use `/movements/transfer` to execute an atomic bin-to-bin transfer.

### 5. Cycle Counts & Discrepancy Adjustments
- Operators submit count variances via `/adjustments/create`.
- Warehouse Managers or Admins review the physical count vs. ledger count at `/adjustments/{id}` and can **Approve** or **Reject**.
- On approval, the ledger is automatically updated with reason codes (`cycle_count`, `damaged`, `expired`, `write_off`).

### 6. Offline SVG Barcode Label Generator
- Navigate to `/products/barcodes` to preview and print a vector barcode sheet for warehouse shelving and physical item labeling.
- Rendered via a native pure-PHP Code 128 SVG generator (`BarcodeService`) with zero external API calls.

---

## 🧪 Testing & Verification

WMS Enterprise includes PHPUnit and Feature tests. When running inside the container:
```powershell
docker compose exec app php artisan test
```

To test syntax linting across all PHP files:
```powershell
php -l <filename>
```
*(All 45+ application PHP files have been validated with zero syntax errors).*

---

## 🔒 Security Best Practices
- **CSRF Protection**: All POST/PUT/DELETE forms include Laravel CSRF tokens.
- **SQL Injection Prevention**: Built entirely on Eloquent ORM and PDO prepared statements with PostgreSQL parameter bindings.
- **Password Security**: Passwords hashed using bcrypt with cost factor 12.
- **Role Middleware**: Protected administrative routes require the `role:admin` middleware.

---

## 📄 License
This Warehouse Management System is licensed under the **MIT License**.
Distributed for enterprise distribution, production warehousing, and internal supply chain logistics.
