<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Country;
use App\Models\Province;
use App\Models\Region;
use App\Models\Zonal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationHierarchySeeder extends Seeder
{
    /**
     * Seed a single country → province → zonal → region → branch chain for
     * local development. Every record uses firstOrCreate keyed on its unique
     * code, so re-running the seeder never duplicates or aborts on a code that
     * already exists.
     */
    public function run(): void
    {
        $country = Country::firstOrCreate(
            ['code' => 'LK'],
            [
                'name' => 'Sri Lanka',
                'description' => 'Sri Lanka',
                'is_active' => true,
            ]
        );

        $province = Province::firstOrCreate(
            ['code' => 'PROV-WP'],
            [
                'name' => 'Western Province',
                'country_id' => $country->id,
                'is_active' => true,
            ]
        );

        $zonal = Zonal::firstOrCreate(
            ['code' => 'ZON-CMB'],
            [
                'name' => 'Colombo Zone',
                'province_id' => $province->id,
                'is_active' => true,
            ]
        );

        $region = Region::firstOrCreate(
            ['code' => 'REG-CMB'],
            [
                'name' => 'Colombo Region',
                'zonal_id' => $zonal->id,
                'is_active' => true,
            ]
        );

        Branch::firstOrCreate(
            ['code' => 'BR-MAIN-001'],
            [
                'name' => 'Head Office',
                'group_id' => null,
                'province_id' => $province->id,
                'zone_id' => $zonal->id,
                'region_id' => $region->id,
                'address_line1' => 'No. 10, Galle Road',
                'address_line2' => null,
                'city' => 'Colombo',
                'postal_code' => '00300',
                'phone_primary' => '+94 11 000 0000',
                'phone_secondary' => null,
                'email' => 'headoffice@cdpcapital.lk',
                'fax' => null,
                'opening_date' => now()->toDateString(),
                'branch_type' => 'main',
                'latitude' => 6.9271,
                'longitude' => 79.8612,
                'is_active' => true,
                'is_head_office' => true,
            ]
        );

        $this->command->info('Location hierarchy seeded: Sri Lanka → Western Province → Colombo Zone → Colombo Region → Head Office.');
    }
}