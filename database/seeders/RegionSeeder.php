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
        ['name' => 'Nairobi',        'code' => 'NBI'],
        ['name' => 'Coast',          'code' => 'CST'],
        ['name' => 'North Eastern',  'code' => 'NEA'],
        ['name' => 'Eastern',        'code' => 'EAS'],
        ['name' => 'Central',        'code' => 'CTR'],
        ['name' => 'Rift Valley',    'code' => 'RFT'],
        ['name' => 'Nyanza',         'code' => 'NYZ'],
        ['name' => 'Western',        'code' => 'WST'],
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
