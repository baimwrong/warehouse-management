<?php

namespace App\Services;

use App\Models\BulkImportLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class BulkImportService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Process bulk import for an uploaded file
     */
    public function import(
        string $importType,
        UploadedFile|string $file,
        ?int $userId = null,
        bool $dryRun = false
    ): array {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        $fileName = is_string($file) ? basename($file) : $file->getClientOriginalName();
        $fileSizeKb = is_string($file) ? (int) (filesize($file) / 1024) : (int) ($file->getSize() / 1024);

        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $rows = $this->parseCsv($filePath);

        if (empty($rows)) {
            return [
                'success' => false,
                'total_rows' => 0,
                'successful_rows' => 0,
                'failed_rows' => 0,
                'errors' => ['CSV file is empty or headers could not be read.'],
            ];
        }

        $totalRows = count($rows);
        $successfulRows = 0;
        $failedRows = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $lineNumber = $index + 2; // +1 for header, +1 for 1-based index
                $rowErrors = [];

                switch ($importType) {
                    case 'products':
                        $rowErrors = $this->importProductRow($row, $lineNumber);
                        break;
                    case 'inventory':
                        $rowErrors = $this->importInventoryRow($row, $lineNumber, $userId);
                        break;
                    case 'locations':
                        $rowErrors = $this->importLocationRow($row, $lineNumber);
                        break;
                    case 'suppliers':
                        $rowErrors = $this->importSupplierRow($row, $lineNumber);
                        break;
                    default:
                        $rowErrors[] = "Unsupported import type: {$importType}";
                }

                if (!empty($rowErrors)) {
                    $failedRows++;
                    $errors[] = [
                        'line' => $lineNumber,
                        'data' => $row,
                        'messages' => $rowErrors,
                    ];
                } else {
                    $successfulRows++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            $status = $failedRows === 0 ? 'completed' : ($successfulRows > 0 ? 'partial' : 'failed');

            // Log the operation in PostgreSQL JSONB audit log
            if (!$dryRun) {
                BulkImportLog::create([
                    'user_id' => $userId,
                    'import_type' => $importType,
                    'file_name' => $fileName,
                    'file_size_kb' => $fileSizeKb,
                    'total_rows' => $totalRows,
                    'successful_rows' => $successfulRows,
                    'failed_rows' => $failedRows,
                    'status' => $status,
                    'error_log' => $errors,
                ]);
            }

            return [
                'success' => $failedRows === 0,
                'status' => $status,
                'dry_run' => $dryRun,
                'total_rows' => $totalRows,
                'successful_rows' => $successfulRows,
                'failed_rows' => $failedRows,
                'errors' => $errors,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'status' => 'failed',
                'dry_run' => $dryRun,
                'total_rows' => $totalRows,
                'successful_rows' => 0,
                'failed_rows' => $totalRows,
                'errors' => [['line' => 0, 'messages' => [$e->getMessage()]]],
            ];
        }
    }

    /**
     * Import a single Product row
     */
    protected function importProductRow(array $row, int $lineNumber): array
    {
        $rules = [
            'sku' => 'required|string|max:60',
            'name' => 'required|string|max:200',
            'category_code' => 'nullable|string|max:50',
            'barcode' => 'nullable|string|max:80',
            'uom' => 'nullable|string|in:PCS,BOX,PALLET,KG,LTR,METER',
            'unit_cost' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'min_stock_level' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'volume_cbm' => 'nullable|numeric|min:0',
        ];

        $validator = Validator::make($row, $rules);
        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        $categoryId = null;
        if (!empty($row['category_code'])) {
            $category = Category::firstOrCreate(
                ['code' => trim($row['category_code'])],
                ['name' => ucwords(str_replace('_', ' ', trim($row['category_code'])))]
            );
            $categoryId = $category->id;
        }

        Product::updateOrCreate(
            ['sku' => trim($row['sku'])],
            [
                'name' => trim($row['name']),
                'category_id' => $categoryId,
                'barcode' => !empty($row['barcode']) ? trim($row['barcode']) : null,
                'uom' => !empty($row['uom']) ? strtoupper(trim($row['uom'])) : 'PCS',
                'unit_cost' => (float) ($row['unit_cost'] ?? 0),
                'unit_price' => (float) ($row['unit_price'] ?? 0),
                'min_stock_level' => (float) ($row['min_stock_level'] ?? 10),
                'max_stock_level' => (float) ($row['max_stock_level'] ?? 500),
                'reorder_point' => (float) ($row['reorder_point'] ?? 25),
                'weight_kg' => (float) ($row['weight_kg'] ?? 0),
                'volume_cbm' => (float) ($row['volume_cbm'] ?? 0),
                'description' => $row['description'] ?? null,
                'is_active' => true,
            ]
        );

        return [];
    }

    /**
     * Import a single Inventory row
     */
    protected function importInventoryRow(array $row, int $lineNumber, ?int $userId = null): array
    {
        $rules = [
            'warehouse_code' => 'required|string',
            'location_code' => 'required|string',
            'sku' => 'required|string',
            'quantity' => 'required|numeric|min:0.0001',
            'batch_number' => 'nullable|string',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];

        $validator = Validator::make($row, $rules);
        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        $warehouse = Warehouse::where('code', trim($row['warehouse_code']))->first();
        if (!$warehouse) {
            return ["Warehouse code '{$row['warehouse_code']}' does not exist."];
        }

        $location = StorageLocation::where('location_code', trim($row['location_code']))->first();
        if (!$location) {
            return ["Storage bin code '{$row['location_code']}' does not exist."];
        }

        if ($location->warehouse_id !== $warehouse->id) {
            return ["Storage bin '{$row['location_code']}' does not belong to warehouse '{$row['warehouse_code']}'."];
        }

        $product = Product::where('sku', trim($row['sku']))->first();
        if (!$product) {
            return ["Product SKU '{$row['sku']}' does not exist."];
        }

        $batchId = null;
        if (!empty($row['batch_number'])) {
            $batch = ProductBatch::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'batch_number' => trim($row['batch_number']),
                ]
            );
            $batchId = $batch->id;
        }

        $this->inventoryService->increaseStock(
            warehouseId: $warehouse->id,
            locationId: $location->id,
            productId: $product->id,
            quantity: (float) $row['quantity'],
            batchId: $batchId,
            referenceType: 'bulk_import',
            referenceId: null,
            unitCost: !empty($row['unit_cost']) ? (float) $row['unit_cost'] : (float) $product->unit_cost,
            userId: $userId,
            notes: $row['notes'] ?? 'Admin bulk initial stock import'
        );

        return [];
    }

    /**
     * Import a single Location row
     */
    protected function importLocationRow(array $row, int $lineNumber): array
    {
        $rules = [
            'warehouse_code' => 'required|string',
            'zone_code' => 'required|string',
            'location_code' => 'required|string|max:50',
            'aisle' => 'nullable|string|max:20',
            'rack' => 'nullable|string|max:20',
            'shelf' => 'nullable|string|max:20',
            'bin' => 'nullable|string|max:20',
            'max_weight_kg' => 'nullable|numeric|min:0',
            'max_volume_cbm' => 'nullable|numeric|min:0',
        ];

        $validator = Validator::make($row, $rules);
        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        $warehouse = Warehouse::where('code', trim($row['warehouse_code']))->first();
        if (!$warehouse) {
            return ["Warehouse code '{$row['warehouse_code']}' does not exist."];
        }

        $zone = WarehouseZone::firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'code' => trim($row['zone_code']),
            ],
            [
                'name' => 'Zone ' . trim($row['zone_code']),
                'zone_type' => 'storage',
            ]
        );

        StorageLocation::updateOrCreate(
            ['location_code' => trim($row['location_code'])],
            [
                'warehouse_id' => $warehouse->id,
                'warehouse_zone_id' => $zone->id,
                'aisle' => $row['aisle'] ?? null,
                'rack' => $row['rack'] ?? null,
                'shelf' => $row['shelf'] ?? null,
                'bin' => $row['bin'] ?? null,
                'max_weight_kg' => (float) ($row['max_weight_kg'] ?? 1000.0),
                'max_volume_cbm' => (float) ($row['max_volume_cbm'] ?? 2.5),
                'is_active' => true,
            ]
        );

        return [];
    }

    /**
     * Import a single Supplier row
     */
    protected function importSupplierRow(array $row, int $lineNumber): array
    {
        $rules = [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:50',
        ];

        $validator = Validator::make($row, $rules);
        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        Supplier::updateOrCreate(
            ['code' => trim($row['code'])],
            [
                'name' => trim($row['name']),
                'contact_person' => $row['contact_person'] ?? null,
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
                'city' => $row['city'] ?? null,
                'country' => $row['country'] ?? 'United States',
                'tax_id' => $row['tax_id'] ?? null,
                'payment_terms' => $row['payment_terms'] ?? 'Net 30',
                'status' => 'active',
            ]
        );

        return [];
    }

    /**
     * Parse CSV file into associative array
     */
    public function parseCsv(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [];
        }

        $rows = [];
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = strpos($firstLine, ';') !== false && strpos($firstLine, ',') === false ? ';' : ',';

        $header = null;
        while (($data = fgetcsv($handle, 4096, $delimiter)) !== false) {
            // Strip UTF-8 BOM from first header column if present
            if ($header === null) {
                if (isset($data[0])) {
                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
                }
                $header = array_map(fn($col) => strtolower(trim($col)), $data);
                continue;
            }

            // Skip empty rows
            if (empty(array_filter($data, fn($val) => trim($val) !== ''))) {
                continue;
            }

            if (count($data) === count($header)) {
                $rows[] = array_combine($header, array_map('trim', $data));
            } elseif (count($data) < count($header)) {
                // Pad missing fields with null
                $data = array_pad($data, count($header), null);
                $rows[] = array_combine($header, array_map('trim', $data));
            } else {
                // Trim surplus trailing columns
                $trimmedData = array_slice($data, 0, count($header));
                $rows[] = array_combine($header, array_map('trim', $trimmedData));
            }
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Get sample CSV data string for template download
     */
    public function getSampleTemplate(string $type): string
    {
        return match ($type) {
            'products' => "sku,barcode,name,description,category_code,uom,unit_cost,unit_price,min_stock_level,max_stock_level,reorder_point,weight_kg,volume_cbm\n" .
                "ELEC-DRN-001,893521094001,Pro Surveillance Drone,High altitude industrial camera drone,ELECTRONICS,PCS,350.00,599.99,10,100,20,1.250,0.0150\n" .
                "ELEC-BAT-002,893521094002,Lithium Battery Pack 5000mAh,High capacity replacement battery,PARTS,PCS,25.50,49.99,50,500,100,0.350,0.0020\n" .
                "TOOL-WRN-101,893521094003,Industrial Ratchet Wrench Set 24pc,Chrome vanadium steel mechanic toolkit,TOOLS,BOX,45.00,89.50,15,80,25,3.200,0.0220\n",

            'inventory' => "warehouse_code,location_code,sku,quantity,batch_number,unit_cost,notes\n" .
                "WH-CENTRAL,WH01-ZN-A-R01-S01-B01,ELEC-DRN-001,25,BATCH-2026-A1,350.00,Initial stock balance\n" .
                "WH-CENTRAL,WH01-ZN-A-R01-S01-B02,ELEC-BAT-002,150,BATCH-2026-B2,25.50,Initial stock balance\n" .
                "WH-CENTRAL,WH01-ZN-B-R02-S01-B01,TOOL-WRN-101,40,BATCH-2026-C1,45.00,Initial stock balance\n",

            'locations' => "warehouse_code,zone_code,location_code,aisle,rack,shelf,bin,max_weight_kg,max_volume_cbm\n" .
                "WH-CENTRAL,ZN-A,WH01-ZN-A-R01-S01-B01,A01,R01,S01,B01,1500.00,3.00\n" .
                "WH-CENTRAL,ZN-A,WH01-ZN-A-R01-S01-B02,A01,R01,S01,B02,1500.00,3.00\n" .
                "WH-CENTRAL,ZN-B,WH01-ZN-B-R02-S01-B01,B02,R02,S01,B01,2000.00,4.50\n",

            'suppliers' => "code,name,contact_person,email,phone,address,city,country,tax_id,payment_terms\n" .
                "SUPP-TECH-01,Global Tech Components Ltd,Marcus Vance,sales@globaltech.com,+1-555-019-2834,104 Industrial Way,San Jose,United States,US-9481726,Net 30\n" .
                "SUPP-AERO-02,Precision Avionics Dynamics,Elena Rostova,orders@precision-aero.com,+1-555-028-4910,88 Aerospace Blvd,Seattle,United States,US-8841920,Net 60\n" .
                "SUPP-TOOL-03,Titan Industrial Tools Inc,Kenji Sato,info@titantools.com,+1-555-037-1829,420 Forge St,Detroit,United States,US-7738192,Net 15\n",

            default => "sku,name\nSAMPLE-01,Sample Item\n"
        };
    }
}
