<?php

namespace Database\Seeders;

use App\Models\SlaConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;

class SlaConfigurationSeeder extends Seeder
{
    /**
     * SLA targets per severity from the FOAMS SRS.
     * Hours are read from this array — never hardcoded elsewhere.
     *
     * @var list<array{severity: string, acknowledge_within_hrs: int, resolve_within_hrs: int}>
     */
    private array $slaTargets = [
        ['severity' => 'critical', 'acknowledge_within_hrs' => 1,  'resolve_within_hrs' => 4],
        ['severity' => 'high',     'acknowledge_within_hrs' => 4,  'resolve_within_hrs' => 8],
        ['severity' => 'medium',   'acknowledge_within_hrs' => 8,  'resolve_within_hrs' => 24],
        ['severity' => 'low',      'acknowledge_within_hrs' => 24, 'resolve_within_hrs' => 72],
    ];

    public function run(): void
    {
        $adminUser = User::role('admin')->firstOrFail();

        foreach ($this->slaTargets as $target) {
            SlaConfiguration::firstOrCreate(
                ['severity' => $target['severity']],
                [
                    'acknowledge_within_hrs' => $target['acknowledge_within_hrs'],
                    'resolve_within_hrs' => $target['resolve_within_hrs'],
                    'effective_from' => now(),
                    'created_by_user_id' => $adminUser->id,
                ],
            );
        }
    }
}
