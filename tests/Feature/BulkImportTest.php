<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use App\Services\BulkImportService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected BulkImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importService = app(BulkImportService::class);
    }

    public function test_sample_template_generation_returns_valid_csv(): void
    {
        $productsTemplate = $this->importService->getSampleTemplate('products');
        $this->assertStringContainsString('sku,barcode,name', $productsTemplate);

        $inventoryTemplate = $this->importService->getSampleTemplate('inventory');
        $this->assertStringContainsString('warehouse_code,location_code,sku', $inventoryTemplate);
    }

    public function test_dry_run_mode_does_not_persist_records(): void
    {
        $csvContent = "sku,barcode,name,description,category_code,uom,unit_cost,unit_price,min_stock_level,max_stock_level,reorder_point,weight_kg,volume_cbm\n" .
                      "TEST-SKU-999,123456789,Test Drone,Test,TEST_CAT,PCS,100,200,5,50,10,1,0.01\n";

        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $result = $this->importService->import('products', $file, null, true);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['dry_run']);
        $this->assertEquals(1, $result['successful_rows']);

        // Verify record was rolled back
        $this->assertDatabaseMissing('products', ['sku' => 'TEST-SKU-999']);
    }
}
