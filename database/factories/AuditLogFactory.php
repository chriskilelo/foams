<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event' => fake()->randomElement(['created', 'updated', 'deleted', 'status_changed']),
            'auditable_type' => Issue::class,
            'auditable_id' => Issue::factory(),
            'old_values' => null,
            'new_values' => ['status' => 'new'],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
