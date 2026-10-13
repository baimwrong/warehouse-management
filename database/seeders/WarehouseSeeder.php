<?php

namespace Database\Seeders;

use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehousesData = [
            [
                'code' => 'WH-CENTRAL',
                'name' => 'Central Fulfillment Hub',
                'address' => '4500 Logistics Parkway',
                'city' => 'Chicago',
                'state' => 'IL',
                'postal_code' => '60666',
                'country' => 'United States',
                'contact_email' => 'chicago.ops@wms.internal',
                'contact_phone' => '+1-312-555-0199',
                'capacity_cbm' => 25000.00,
                'status' => 'active',
                'zones' => [
                    [
                        'code' => 'ZN-A',
                        'name' => 'High-Bay Rack Zone A',
                        'zone_type' => 'storage',
                        'description' => 'Fast-moving electronics and robotics',
                        'aisles' => ['A01', 'A02'],
                        'racks' => ['R01', 'R02'],
                        'shelves' => ['S01', 'S02'],
                        'bins' => ['B01', 'B02'],
                    ],
                    [
                        'code' => 'ZN-B',
                        'name' => 'Heavy Industrial Zone B',
                        'zone_type' => 'storage',
                        'description' => 'Heavy tools and hydraulic machinery',
                        'aisles' => ['B01'],
                        'racks' => ['R01', 'R02'],
                        'shelves' => ['S01', 'S02'],
                        'bins' => ['B01', 'B02'],
                    ],
                    [
                        'code' => 'ZN-C',
                        'name' => 'Climate Control Zone C',
                        'zone_type' => 'cold_storage',
                        'description' => 'Pharmaceuticals and heat-sensitive chemicals',
                        'aisles' => ['C01'],
                        'racks' => ['R01'],
                        'shelves' => ['S01', 'S02'],
                        'bins' => ['B01', 'B02'],
                    ],
                    [
                        'code' => 'ZN-REC',
                        'name' => 'Dock Receiving Bay',
                        'zone_type' => 'receiving',
                        'description' => 'Inbound inspection staging',
                        'aisles' => ['REC'],
                        'racks' => ['R01'],
                        'shelves' => ['S01'],
                        'bins' => ['B01', 'B02'],
                    ],
                    [
                        'code' => 'ZN-SHIP',
                        'name' => 'Dispatch Staging Bay',
                        'zone_type' => 'dispatch',
                        'description' => 'Carrier pick-up dock',
                        'aisles' => ['DSP'],
                        'racks' => ['R01'],
                        'shelves' => ['S01'],
                        'bins' => ['B01', 'B02'],
                    ],
                ]
            ],
            [
                'code' => 'WH-WEST',
                'name' => 'Pacific Gateway Depot',
                'address' => '1200 Harbour Blvd',
                'city' => 'Long Beach',
                'state' => 'CA',
                'postal_code' => '90802',
                'country' => 'United States',
                'contact_email' => 'west.ops@wms.internal',
                'contact_phone' => '+1-562-555-0144',
                'capacity_cbm' => 18000.00,
                'status' => 'active',
                'zones' => [
                    [
                        'code' => 'ZN-WEST-A',
                        'name' => 'Electronics Import Zone',
                        'zone_type' => 'storage',
                        'description' => 'Import container devanning',
                        'aisles' => ['W01'],
                        'racks' => ['R01', 'R02'],
                        'shelves' => ['S01', 'S02'],
                        'bins' => ['B01', 'B02'],
                    ],
                ]
            ],
        ];

        foreach ($warehousesData as $wData) {
            $zones = $wData['zones'];
            unset($wData['zones']);

            $warehouse = Warehouse::updateOrCreate(['code' => $wData['code']], $wData);

            foreach ($zones as $zData) {
                $aisles = $zData['aisles'];
                $racks = $zData['racks'];
                $shelves = $zData['shelves'];
                $bins = $zData['bins'];
                unset($zData['aisles'], $zData['racks'], $zData['shelves'], $zData['bins']);

                $zData['warehouse_id'] = $warehouse->id;
                $zone = WarehouseZone::updateOrCreate(
                    ['warehouse_id' => $warehouse->id, 'code' => $zData['code']],
                    $zData
                );

                // Generate hierarchical storage locations
                foreach ($aisles as $aisle) {
                    foreach ($racks as $rack) {
                        foreach ($shelves as $shelf) {
                            foreach ($bins as $bin) {
                                $locationCode = sprintf(
                                    "%s-%s-%s-%s-%s-%s",
                                    substr($warehouse->code, 0, 4),
                                    $zone->code,
                                    $aisle,
                                    $rack,
                                    $shelf,
                                    $bin
                                );

                                StorageLocation::updateOrCreate(
                                    ['location_code' => $locationCode],
                                    [
                                        'warehouse_id' => $warehouse->id,
                                        'warehouse_zone_id' => $zone->id,
                                        'aisle' => $aisle,
                                        'rack' => $rack,
                                        'shelf' => $shelf,
                                        'bin' => $bin,
                                        'max_weight_kg' => 1500.00,
                                        'max_volume_cbm' => 3.50,
                                        'is_active' => true,
                                        'is_occupied' => false,
                                        'barcode' => 'LOC-' . crc32($locationCode),
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}
