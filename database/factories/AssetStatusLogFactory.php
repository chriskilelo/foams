<?php

namespace Database\Factories;

use App\Enums\AssetLogStatus;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssetStatusLog>
 */
class AssetStatusLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'user_id' => User::factory(),
            'logged_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'observed_at' => fake()->time('H:i:s'),
            'status' => fake()->randomElement(AssetLogStatus::cases()),
            'throughput_mbps' => fake()->randomFloat(2, 0.5, 100.0),
            'remarks' => fake()->sentence(),
            'latitude' => fake()->latitude(-4.7, 4.7),
            'longitude' => fake()->longitude(33.9, 41.9),
            'is_amendment' => false,
            'amendment_reason' => null,
            'synced_at' => now(),
        ];
    }

    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'synced_at' => null,
        ]);
    }

    public function amendment(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_amendment' => true,
            'amendment_reason' => fake()->sentence(),
        ]);
    }
}
