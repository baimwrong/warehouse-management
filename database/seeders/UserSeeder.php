<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Alexander Vance (Super Admin)',
                'email' => 'admin@wms.internal',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+1-555-010-0001',
                'is_active' => true,
            ],
            [
                'name' => 'Sarah Connor (Warehouse Manager)',
                'email' => 'manager@wms.internal',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'phone' => '+1-555-010-0002',
                'is_active' => true,
            ],
            [
                'name' => 'David Miller (Inventory Operator)',
                'email' => 'operator@wms.internal',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'phone' => '+1-555-010-0003',
                'is_active' => true,
            ],
            [
                'name' => 'Elena Rostova (Lead Dispatcher)',
                'email' => 'dispatcher@wms.internal',
                'password' => Hash::make('password'),
                'role' => 'dispatcher',
                'phone' => '+1-555-010-0004',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(['email' => $userData['email']], $userData);
        }
    }
}
