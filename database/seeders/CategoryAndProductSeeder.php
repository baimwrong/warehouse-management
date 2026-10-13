<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CategoryAndProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'ELECTRONICS', 'name' => 'Industrial Electronics', 'description' => 'Sensors, microcontrollers, communication modules'],
            ['code' => 'ROBOTICS', 'name' => 'Robotics & Automation', 'description' => 'Servos, actuator arms, LiDAR scanners'],
            ['code' => 'TOOLS', 'name' => 'Heavy Duty Industrial Tools', 'description' => 'Pneumatic tools, calibration gauges, sockets'],
            ['code' => 'SAFETY', 'name' => 'PPE & Workplace Safety', 'description' => 'Respirators, safety suits, first aid response'],
            ['code' => 'COLD_CHAIN', 'name' => 'Cold Chain Pharma', 'description' => 'Insulin kits, vaccines, reagent enzymes'],
            ['code' => 'FILTRATION', 'name' => 'Industrial Filtration', 'description' => 'HEPA filters, hydraulic filters, activated carbon'],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $category = Category::updateOrCreate(['code' => $cat['code']], $cat);
            $categoryMap[$cat['code']] = $category->id;
        }

        $products = [
            [
                'sku' => 'ELEC-DRN-001',
                'barcode' => '893521094001',
                'name' => 'Pro Autonomous Surveillance Drone',
                'description' => 'High-altitude autonomous inspection drone with thermal sensor',
                'category_id' => $categoryMap['ELECTRONICS'],
                'uom' => 'PCS',
                'min_stock_level' => 5,
                'max_stock_level' => 50,
                'reorder_point' => 15,
                'unit_cost' => 450.00,
                'unit_price' => 799.99,
                'weight_kg' => 1.85,
                'volume_cbm' => 0.025,
            ],
            [
                'sku' => 'ELEC-LIDAR-002',
                'barcode' => '893521094002',
                'name' => '360-Degree Industrial Solid-State LiDAR',
                'description' => 'Precision navigational range finder for AGV automated forklifts',
                'category_id' => $categoryMap['ROBOTICS'],
                'uom' => 'PCS',
                'min_stock_level' => 10,
                'max_stock_level' => 80,
                'reorder_point' => 20,
                'unit_cost' => 220.00,
                'unit_price' => 449.00,
                'weight_kg' => 0.45,
                'volume_cbm' => 0.005,
            ],
            [
                'sku' => 'ELEC-BAT-003',
                'barcode' => '893521094003',
                'name' => 'Lithium Iron Phosphate Battery Pack 48V 100Ah',
                'description' => 'Modular heavy equipment power pack for material handling',
                'category_id' => $categoryMap['ELECTRONICS'],
                'uom' => 'PCS',
                'min_stock_level' => 8,
                'max_stock_level' => 60,
                'reorder_point' => 15,
                'unit_cost' => 680.00,
                'unit_price' => 1150.00,
                'weight_kg' => 28.50,
                'volume_cbm' => 0.080,
            ],
            [
                'sku' => 'TOOL-WRN-101',
                'barcode' => '893521094004',
                'name' => 'Industrial Ratchet Wrench Set 24pc',
                'description' => 'Heat-treated chrome-vanadium mechanic wrench assortment',
                'category_id' => $categoryMap['TOOLS'],
                'uom' => 'BOX',
                'min_stock_level' => 15,
                'max_stock_level' => 150,
                'reorder_point' => 30,
                'unit_cost' => 42.50,
                'unit_price' => 89.95,
                'weight_kg' => 3.40,
                'volume_cbm' => 0.018,
            ],
            [
                'sku' => 'TOOL-CAL-102',
                'barcode' => '893521094005',
                'name' => 'Digital Micrometer Caliper Precision Gauge',
                'description' => 'IP67 waterproof 0.001mm tolerance digital measurement tool',
                'category_id' => $categoryMap['TOOLS'],
                'uom' => 'PCS',
                'min_stock_level' => 12,
                'max_stock_level' => 100,
                'reorder_point' => 25,
                'unit_cost' => 65.00,
                'unit_price' => 135.00,
                'weight_kg' => 0.65,
                'volume_cbm' => 0.004,
            ],
            [
                'sku' => 'SAFE-RESP-201',
                'barcode' => '893521094006',
                'name' => 'PAPR Powered Air Purifying Respirator Kit',
                'description' => 'Full face hazmat certified air supply unit for chemical handling',
                'category_id' => $categoryMap['SAFETY'],
                'uom' => 'PCS',
                'min_stock_level' => 6,
                'max_stock_level' => 40,
                'reorder_point' => 12,
                'unit_cost' => 310.00,
                'unit_price' => 595.00,
                'weight_kg' => 2.10,
                'volume_cbm' => 0.030,
            ],
            [
                'sku' => 'SAFE-FAK-202',
                'barcode' => '893521094007',
                'name' => 'Comprehensive Trauma & Hemostatic Emergency Kit',
                'description' => 'OSHA certified wall-mounted warehouse trauma kit with tourniquets',
                'category_id' => $categoryMap['SAFETY'],
                'uom' => 'BOX',
                'min_stock_level' => 10,
                'max_stock_level' => 80,
                'reorder_point' => 20,
                'unit_cost' => 55.00,
                'unit_price' => 110.00,
                'weight_kg' => 2.75,
                'volume_cbm' => 0.022,
            ],
            [
                'sku' => 'COLD-VAC-301',
                'barcode' => '893521094008',
                'name' => 'Ultra-Cold mRNA Vaccine Vials (Tray of 50)',
                'description' => 'Requires -80C to -20C continuous cold chain monitoring',
                'category_id' => $categoryMap['COLD_CHAIN'],
                'uom' => 'BOX',
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'reorder_point' => 40,
                'unit_cost' => 120.00,
                'unit_price' => 240.00,
                'weight_kg' => 1.20,
                'volume_cbm' => 0.008,
            ],
            [
                'sku' => 'FLTR-HEPA-401',
                'barcode' => '893521094009',
                'name' => 'Cleanroom Class 100 HEPA Filter Cartridge 24x24x12',
                'description' => '99.99% particulate capture efficiency for manufacturing clean rooms',
                'category_id' => $categoryMap['FILTRATION'],
                'uom' => 'PCS',
                'min_stock_level' => 15,
                'max_stock_level' => 120,
                'reorder_point' => 30,
                'unit_cost' => 78.00,
                'unit_price' => 149.00,
                'weight_kg' => 4.50,
                'volume_cbm' => 0.115,
            ],
        ];

        foreach ($products as $pData) {
            $product = Product::updateOrCreate(['sku' => $pData['sku']], $pData);

            // Create batch lots for products
            ProductBatch::updateOrCreate(
                ['product_id' => $product->id, 'batch_number' => 'LOT-2026-X1'],
                [
                    'mfg_date' => Carbon::now()->subMonths(2),
                    'expiry_date' => Carbon::now()->addMonths(16),
                    'notes' => 'Factory Q1 release',
                ]
            );

            ProductBatch::updateOrCreate(
                ['product_id' => $product->id, 'batch_number' => 'LOT-2026-X2'],
                [
                    'mfg_date' => Carbon::now()->subMonth(),
                    'expiry_date' => Carbon::now()->addMonths(22),
                    'notes' => 'Factory Q2 release',
                ]
            );
        }
    }
}
