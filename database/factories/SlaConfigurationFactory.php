<?php

namespace Database\Factories;

use App\Enums\IssueSeverity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlaConfiguration>
 */
class SlaConfigurationFactory extends Factory
{
    /** @var array<string, array{int, int}> */
    private const SLA_HOURS = [
        'critical' => [1, 4],
        'high' => [4, 8],
        'medium' => [8, 24],
        'low' => [24, 72],
    ];

    public function definition(): array
    {
        $severity = fake()->randomElement(IssueSeverity::cases());
        [$ack, $resolve] = self::SLA_HOURS[$severity->value];

        return [
            'severity' => $severity,
            'acknowledge_within_hrs' => $ack,
            'resolve_within_hrs' => $resolve,
            'effective_from' => now(),
            'created_by_user_id' => User::factory(),
        ];
    }

    public function forSeverity(IssueSeverity $severity): static
    {
        [$ack, $resolve] = self::SLA_HOURS[$severity->value];

        return $this->state(fn (array $attributes) => [
            'severity' => $severity,
            'acknowledge_within_hrs' => $ack,
            'resolve_within_hrs' => $resolve,
        ]);
    }
}
