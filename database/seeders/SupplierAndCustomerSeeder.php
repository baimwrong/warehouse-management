<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierAndCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'SUPP-NEXUS-01',
                'name' => 'Nexus Microelectronics Corp',
                'contact_person' => 'Marcus Vance',
                'email' => 'orders@nexus-micro.com',
                'phone' => '+1-408-555-0123',
                'address' => '3300 Innovation Way',
                'city' => 'San Jose',
                'country' => 'United States',
                'tax_id' => 'US-88491023',
                'payment_terms' => 'Net 30',
                'status' => 'active',
            ],
            [
                'code' => 'SUPP-ROBO-02',
                'name' => 'Kinetics Robotics & Automation GmbH',
                'contact_person' => 'Helena Schmidt',
                'email' => 'sales@kinetics-robotics.de',
                'phone' => '+49-89-555-0988',
                'address' => 'Industriestrasse 44',
                'city' => 'Munich',
                'country' => 'Germany',
                'tax_id' => 'DE-99482711',
                'payment_terms' => 'Net 60',
                'status' => 'active',
            ],
            [
                'code' => 'SUPP-TITAN-03',
                'name' => 'Titan Industrial Forging & Tools',
                'contact_person' => 'Robert Callahan',
                'email' => 'b2b@titantools.com',
                'phone' => '+1-313-555-0455',
                'address' => '1080 Foundry Blvd',
                'city' => 'Detroit',
                'country' => 'United States',
                'tax_id' => 'US-77381920',
                'payment_terms' => 'Net 15',
                'status' => 'active',
            ],
            [
                'code' => 'SUPP-BIO-04',
                'name' => 'BioGuard Pharma & Life Sciences',
                'contact_person' => 'Dr. Claire Laurent',
                'email' => 'orders@bioguard-pharma.ch',
                'phone' => '+41-22-555-0761',
                'address' => 'Rue du Rhone 15',
                'city' => 'Geneva',
                'country' => 'Switzerland',
                'tax_id' => 'CH-55198273',
                'payment_terms' => 'Net 30',
                'status' => 'active',
            ],
        ];

        foreach ($suppliers as $sData) {
            Supplier::updateOrCreate(['code' => $sData['code']], $sData);
        }

        $customers = [
            [
                'code' => 'CUST-AERO-01',
                'name' => 'Vanguard Aerospace Systems',
                'contact_person' => 'Commander James Hayes',
                'email' => 'procurement@vanguard-aero.com',
                'phone' => '+1-206-555-0891',
                'shipping_address' => 'Hangar 4B, King County Int Airport',
                'billing_address' => '100 Aerospace Plaza, Ste 400',
                'city' => 'Seattle',
                'country' => 'United States',
                'status' => 'active',
            ],
            [
                'code' => 'CUST-AUTONOMY-02',
                'name' => 'WayPoint Autonomous Fleet Logistics',
                'contact_person' => 'Rachel Chang',
                'email' => 'fleet-ops@waypoint-autonomous.io',
                'phone' => '+1-415-555-0377',
                'shipping_address' => '550 Mission St, Dock 2',
                'billing_address' => '550 Mission St, Suite 1200',
                'city' => 'San Francisco',
                'country' => 'United States',
                'status' => 'active',
            ],
            [
                'code' => 'CUST-MED-03',
                'name' => 'Metropolitan Health & Trauma Center',
                'contact_person' => 'Dr. Angela Martinez',
                'email' => 'materials@metro-trauma-health.org',
                'phone' => '+1-312-555-0943',
                'shipping_address' => 'Central Receiving Bay, 700 S Paulina St',
                'billing_address' => 'Finance Dept, 700 S Paulina St',
                'city' => 'Chicago',
                'country' => 'United States',
                'status' => 'active',
            ],
        ];

        foreach ($customers as $cData) {
            Customer::updateOrCreate(['code' => $cData['code']], $cData);
        }
    }
}
