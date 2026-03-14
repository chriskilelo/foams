<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            RegionSeeder::class,
            CountySeeder::class,
            UserSeeder::class,           // must run before SlaConfigurationSeeder (FK dependency)
            SlaConfigurationSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
