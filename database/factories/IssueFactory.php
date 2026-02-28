<?php

namespace Database\Factories;

use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\ReporterCategory;
use App\Models\Asset;
use App\Models\County;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Issue>
 */
class IssueFactory extends Factory
{
    private static int $sequence = 0;

    public function definition(): array
    {
        $severity = fake()->randomElement(IssueSeverity::cases());

        return [
            'reference_number' => 'ISS-'.str_pad(++self::$sequence, 4, '0', STR_PAD_LEFT),
            'asset_id' => Asset::factory(),
            'county_id' => County::factory(),
            'issue_type' => fake()->randomElement(['connectivity', 'hardware_failure', 'power_outage', 'vandalism', 'performance_degradation']),
            'severity' => $severity,
            'status' => IssueStatus::New,
            'reporter_category' => fake()->randomElement(ReporterCategory::cases()),
            'reporter_name' => fake()->name(),
            'reporter_email' => fake()->safeEmail(),
            'reporter_phone' => null,
            'created_by_user_id' => User::factory(),
            'assigned_to_user_id' => null,
            'description' => fake()->paragraphs(2, true),
            'workaround_applied' => false,
            'duplicate_of_id' => null,
            'acknowledged_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
            'sla_due_at' => now()->addHours(4),
            'sla_breached' => false,
            'is_escalated' => false,
            'escalated_at' => null,
            'escalated_by_user_id' => null,
        ];
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => IssueSeverity::Critical,
            'sla_due_at' => now()->addHour(),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IssueStatus::Resolved,
            'acknowledged_at' => now()->subHours(3),
            'resolved_at' => now(),
        ]);
    }

    public function escalated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IssueStatus::Escalated,
            'is_escalated' => true,
            'escalated_at' => now(),
        ]);
    }

    public function breached(): static
    {
        return $this->state(fn (array $attributes) => [
            'sla_breached' => true,
            'sla_due_at' => now()->subHour(),
        ]);
    }
}
