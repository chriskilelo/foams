<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\County;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Asset>
 */
class AssetFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(AssetType::cases());
        $prefix = match ($type) {
            AssetType::WifiHotspot => 'WIFI',
            AssetType::NofbiNode => 'NOFBI',
            AssetType::OgnEquipment => 'OGN',
        };

        return [
            'asset_code' => $prefix.'-'.strtoupper(fake()->unique()->lexify('???')).'-'.fake()->unique()->numberBetween(1, 999),
            'name' => fake()->sentence(3),
            'type' => $type,
            'county_id' => County::factory(),
            'location_name' => fake()->streetName(),
            'latitude' => fake()->latitude(-4.7, 4.7),
            'longitude' => fake()->longitude(33.9, 41.9),
            'assigned_to' => null,
            'installation_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'manufacturer' => fake()->company(),
            'model' => fake()->bothify('Model-##??'),
            'serial_number' => fake()->unique()->bothify('SN-########'),
            'status' => fake()->randomElement(AssetStatus::cases()),
        ];
    }

    public function operational(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::Operational,
        ]);
    }

    public function down(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::Down,
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $user->id,
        ]);
    }
}
