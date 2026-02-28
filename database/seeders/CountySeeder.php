<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Region;
use Illuminate\Database\Seeder;

class CountySeeder extends Seeder
{
    /**
     * All 47 Kenyan counties mapped to their official administrative region.
     * Codes are the official 2-digit county codes (01–47).
     *
     * @var array<string, list<array{name: string, code: string}>>
     */
    private array $counties = [
        'Nairobi' => [
            ['name' => 'Nairobi City', 'code' => '047'],
        ],

        'Central' => [
            ['name' => 'Kiambu',    'code' => '022'],
            ['name' => 'Murang\'a', 'code' => '021'],
            ['name' => 'Kirinyaga', 'code' => '020'],
            ['name' => 'Nyeri',     'code' => '019'],
            ['name' => 'Nyandarua', 'code' => '018'],
        ],

        'Coast' => [
            ['name' => 'Mombasa',   'code' => '001'],
            ['name' => 'Kwale',     'code' => '002'],
            ['name' => 'Kilifi',    'code' => '003'],
            ['name' => 'Tana River', 'code' => '004'],
            ['name' => 'Lamu',      'code' => '005'],
            ['name' => 'Taita Taveta', 'code' => '006'],
        ],

        'North Eastern' => [
            ['name' => 'Garissa',   'code' => '007'],
            ['name' => 'Wajir',     'code' => '008'],
            ['name' => 'Mandera',   'code' => '009'],
        ],

        'Eastern' => [
            ['name' => 'Marsabit',  'code' => '010'],
            ['name' => 'Isiolo',    'code' => '011'],
            ['name' => 'Meru',      'code' => '012'],
            ['name' => 'Tharaka-Nithi', 'code' => '013'],
            ['name' => 'Embu',      'code' => '014'],
            ['name' => 'Kitui',     'code' => '015'],
            ['name' => 'Machakos',  'code' => '016'],
            ['name' => 'Makueni',   'code' => '017'],
        ],

        'Rift Valley' => [
            ['name' => 'Turkana',   'code' => '023'],
            ['name' => 'West Pokot', 'code' => '024'],
            ['name' => 'Samburu',   'code' => '025'],
            ['name' => 'Trans Nzoia', 'code' => '026'],
            ['name' => 'Uasin Gishu', 'code' => '027'],
            ['name' => 'Elgeyo-Marakwet', 'code' => '028'],
            ['name' => 'Nandi',     'code' => '029'],
            ['name' => 'Baringo',   'code' => '030'],
            ['name' => 'Laikipia',  'code' => '031'],
            ['name' => 'Nakuru',    'code' => '032'],
            ['name' => 'Narok',     'code' => '033'],
            ['name' => 'Kajiado',   'code' => '034'],
            ['name' => 'Kericho',   'code' => '035'],
            ['name' => 'Bomet',     'code' => '036'],
        ],

        'Western' => [
            ['name' => 'Kakamega',  'code' => '037'],
            ['name' => 'Vihiga',    'code' => '038'],
            ['name' => 'Bungoma',   'code' => '039'],
            ['name' => 'Busia',     'code' => '040'],
        ],

        'Nyanza' => [
            ['name' => 'Siaya',     'code' => '041'],
            ['name' => 'Kisumu',    'code' => '042'],
            ['name' => 'Homa Bay',  'code' => '043'],
            ['name' => 'Migori',    'code' => '044'],
            ['name' => 'Kisii',     'code' => '045'],
            ['name' => 'Nyamira',   'code' => '046'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->counties as $regionName => $counties) {
            $region = Region::where('name', $regionName)->firstOrFail();

            foreach ($counties as $county) {
                County::firstOrCreate(
                    ['code' => $county['code']],
                    ['name' => $county['name'], 'region_id' => $region->id],
                );
            }
        }
    }
}
