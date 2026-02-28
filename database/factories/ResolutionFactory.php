<?php

namespace Database\Factories;

use App\Enums\ResolutionType;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resolution>
 */
class ResolutionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'root_cause' => fake()->paragraph(),
            'steps_taken' => [
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ],
            'resolution_type' => fake()->randomElement(ResolutionType::cases()),
            'resolved_by_user_id' => User::factory(),
            'resolved_at' => now(),
        ];
    }

    public function temporary(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolution_type' => ResolutionType::Temporary,
        ]);
    }

    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolution_type' => ResolutionType::Permanent,
        ]);
    }
}
