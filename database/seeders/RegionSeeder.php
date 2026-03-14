<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Kenya's 8 administrative regions with short codes.
     *
     * @var list<array{name: string, code: string}>
     */
    private array $regions = [
        ['name' => 'Coast',                  'code' => 'CST'],
        ['name' => 'Nyanza',                 'code' => 'NYZ'],
        ['name' => 'Eastern',                'code' => 'EAS'],
        ['name' => 'Western',                'code' => 'WST'],
        ['name' => 'North Rift',             'code' => 'NRF'],
        ['name' => 'South Rift',             'code' => 'SRF'],
        ['name' => 'North Eastern',          'code' => 'NEA'],
        ['name' => 'Nairobi and Central',    'code' => 'NBC'],

    ];

    public function run(): void
    {
        foreach ($this->regions as $region) {
            Region::firstOrCreate(
                ['code' => $region['code']],
                ['name' => $region['name'], 'is_active' => true],
            );
        }
    }
}
