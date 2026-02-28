<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\County>
 */
class CountyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name' => $name,
            'code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4)),
            'region_id' => Region::factory(),
        ];
    }
}
