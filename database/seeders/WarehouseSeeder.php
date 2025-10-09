<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('warehouses')->insert([
            [
                'name' => 'Lagos Central Warehouse',
                'code' => 'LAG-CENTRAL',
                'address' => '12 Wharf Road, Apapa',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'country' => 'Nigeria',
                'contact_person' => 'John Doe',
                'contact_phone' => '08037835670',
                'contact_email' => 'lagoswarehouse@example.com',
                'latitude' => 6.465422,
                'longitude' => 3.406448,
                'description' => 'Main distribution hub for southern Nigeria.',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Abuja Central Warehouse',
                'code' => 'ABJ-CENTRAL',
                'address' => 'Plot 22, Jabi Industrial Layout',
                'city' => 'Abuja',
                'state' => 'FCT',
                'country' => 'Nigeria',
                'contact_person' => 'Jane Smith',
                'contact_phone' => '08037835670',
                'contact_email' => 'abujawarehouse@example.com',
                'latitude' => 9.05785,
                'longitude' => 7.49508,
                'description' => 'Main warehouse serving northern Nigeria.',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
