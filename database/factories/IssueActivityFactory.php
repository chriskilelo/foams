<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IssueActivity>
 */
class IssueActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'user_id' => User::factory(),
            'action_type' => fake()->randomElement(['status_change', 'comment', 'field_note', 'escalation', 'assignment']),
            'previous_status' => null,
            'new_status' => null,
            'comment' => fake()->sentence(),
            'is_internal' => false,
        ];
    }

    public function statusChange(string $from, string $to): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'status_change',
            'previous_status' => $from,
            'new_status' => $to,
            'comment' => null,
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_internal' => true,
        ]);
    }
}
