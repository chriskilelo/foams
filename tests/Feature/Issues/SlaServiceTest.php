<?php

use App\Enums\IssueSeverity;
use App\Events\SlaBreached;
use App\Events\SlaNearingBreach;
use App\Jobs\SendSlaBreachNotification;
use App\Models\County;
use App\Models\Issue;
use App\Models\SlaConfiguration;
use App\Services\SlaService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

// ─── computeDueAt ─────────────────────────────────────────────────────────────

describe('computeDueAt', function () {
    it('computes the correct deadline for a critical issue (4 hours)', function () {
        $county = County::factory()->create();
        SlaConfiguration::factory()->forSeverity(IssueSeverity::Critical)->create([
            'effective_from' => now()->subDay(),
        ]);

        $issue = Issue::factory()->for($county, 'county')->create([
            'severity' => IssueSeverity::Critical,
            'created_at' => now(),
            'asset_id' => null,
        ]);

        $service = new SlaService;
        $dueAt = $service->computeDueAt($issue);

        expect((int) $issue->fresh()->created_at->diffInHours($dueAt))->toBe(4);
    });

    it('computes the correct deadline for a high issue (8 hours)', function () {
        $county = County::factory()->create();
        SlaConfiguration::factory()->forSeverity(IssueSeverity::High)->create([
            'effective_from' => now()->subDay(),
        ]);

        $issue = Issue::factory()->for($county, 'county')->create([
            'severity' => IssueSeverity::High,
            'created_at' => now(),
            'asset_id' => null,
        ]);

        $service = new SlaService;
        $dueAt = $service->computeDueAt($issue);

        expect((int) $issue->fresh()->created_at->diffInHours($dueAt))->toBe(8);
    });

    it('computes the correct deadline for a medium issue (24 hours)', function () {
        $county = County::factory()->create();
        SlaConfiguration::factory()->forSeverity(IssueSeverity::Medium)->create([
            'effective_from' => now()->subDay(),
        ]);

        $issue = Issue::factory()->for($county, 'county')->create([
            'severity' => IssueSeverity::Medium,
            'created_at' => now(),
            'asset_id' => null,
        ]);

        $service = new SlaService;
        $dueAt = $service->computeDueAt($issue);

        expect((int) $issue->fresh()->created_at->diffInHours($dueAt))->toBe(24);
    });

    it('computes the correct deadline for a low issue (72 hours)', function () {
        $county = County::factory()->create();
        SlaConfiguration::factory()->forSeverity(IssueSeverity::Low)->create([
            'effective_from' => now()->subDay(),
        ]);

        $issue = Issue::factory()->for($county, 'county')->create([
            'severity' => IssueSeverity::Low,
            'created_at' => now(),
            'asset_id' => null,
        ]);

        $service = new SlaService;
        $dueAt = $service->computeDueAt($issue);

        expect((int) $issue->fresh()->created_at->diffInHours($dueAt))->toBe(72);
    });

    it('uses the SLA config effective at creation time, not a newer one', function () {
        $county = County::factory()->create();

        // Old config (effective before issue was created)
        SlaConfiguration::factory()->forSeverity(IssueSeverity::High)->create([
            'effective_from' => now()->subDays(10),
            'resolve_within_hrs' => 8,
        ]);

        // Newer config (effective after issue was created — should be ignored)
        SlaConfiguration::factory()->forSeverity(IssueSeverity::High)->create([
            'effective_from' => now()->addHour(),
            'resolve_within_hrs' => 4,
        ]);

        $issue = Issue::factory()->for($county, 'county')->create([
            'severity' => IssueSeverity::High,
            'created_at' => now(),
            'asset_id' => null,
        ]);

        $service = new SlaService;
        $dueAt = $service->computeDueAt($issue);

        // Should use 8 hours (the old config), not 4 hours (the newer config)
        expect((int) $issue->fresh()->created_at->diffInHours($dueAt))->toBe(8);
    });

    it('falls back to 24 hours when no SLA config exists', function () {
        $county = County::factory()->create();

        $issue = Issue::factory()->for($county, 'county')->create([
            'severity' => IssueSeverity::Critical,
            'created_at' => now(),
            'asset_id' => null,
        ]);

        $service = new SlaService;
        $dueAt = $service->computeDueAt($issue);

        expect((int) $issue->fresh()->created_at->diffInHours($dueAt))->toBe(24);
    });
});

// ─── runSlaCheck — breach detection ──────────────────────────────────────────

describe('runSlaCheck — breach detection', function () {
    it('marks overdue issues as sla_breached and dispatches notification', function () {
        Event::fake([SlaBreached::class, SlaNearingBreach::class]);
        Bus::fake([SendSlaBreachNotification::class]);

        $county = County::factory()->create();
        $overdueIssue = Issue::factory()->for($county, 'county')->create([
            'status' => 'in_progress',
            'sla_breached' => false,
            'sla_due_at' => now()->subHour(), // overdue
            'asset_id' => null,
        ]);

        (new SlaService)->runSlaCheck();

        expect($overdueIssue->fresh()->sla_breached)->toBeTrue();

        Event::assertDispatched(SlaBreached::class, fn ($e) => $e->issue->id === $overdueIssue->id);
        Bus::assertDispatched(SendSlaBreachNotification::class, fn ($job) => $job->issue->id === $overdueIssue->id);
    });

    it('does not re-flag issues that are already marked sla_breached', function () {
        Event::fake([SlaBreached::class]);
        Bus::fake([SendSlaBreachNotification::class]);

        $county = County::factory()->create();
        Issue::factory()->for($county, 'county')->create([
            'status' => 'in_progress',
            'sla_breached' => true, // already flagged
            'sla_due_at' => now()->subHour(),
            'asset_id' => null,
        ]);

        (new SlaService)->runSlaCheck();

        Event::assertNotDispatched(SlaBreached::class);
        Bus::assertNotDispatched(SendSlaBreachNotification::class);
    });

    it('does not flag resolved or closed issues', function () {
        Event::fake([SlaBreached::class]);

        $county = County::factory()->create();
        foreach (['resolved', 'closed', 'duplicate'] as $status) {
            Issue::factory()->for($county, 'county')->create([
                'status' => $status,
                'sla_breached' => false,
                'sla_due_at' => now()->subHour(),
                'asset_id' => null,
            ]);
        }

        (new SlaService)->runSlaCheck();

        Event::assertNotDispatched(SlaBreached::class);
    });

    it('flags all open-status issues that are overdue', function () {
        Event::fake([SlaBreached::class]);
        Bus::fake([SendSlaBreachNotification::class]);

        $county = County::factory()->create();
        $openStatuses = ['new', 'acknowledged', 'in_progress', 'pending_third_party', 'escalated'];

        foreach ($openStatuses as $status) {
            Issue::factory()->for($county, 'county')->create([
                'status' => $status,
                'sla_breached' => false,
                'sla_due_at' => now()->subMinutes(10),
                'asset_id' => null,
            ]);
        }

        (new SlaService)->runSlaCheck();

        Event::assertDispatched(SlaBreached::class, count($openStatuses));
    });
});

// ─── runSlaCheck — 50% warning ────────────────────────────────────────────────

describe('runSlaCheck — 50% elapsed warning', function () {
    it('fires SlaNearingBreach for issues past the 50% mark', function () {
        Event::fake([SlaBreached::class, SlaNearingBreach::class]);

        $county = County::factory()->create();

        // Issue created 3h ago, due in 1h → total 4h window, 3h elapsed = 75%
        $nearingIssue = Issue::factory()->for($county, 'county')->create([
            'status' => 'in_progress',
            'sla_breached' => false,
            'created_at' => now()->subHours(3),
            'sla_due_at' => now()->addHour(),
            'asset_id' => null,
        ]);

        (new SlaService)->runSlaCheck();

        Event::assertDispatched(SlaNearingBreach::class, fn ($e) => $e->issue->id === $nearingIssue->id);
    });

    it('does not fire SlaNearingBreach for issues below the 50% mark', function () {
        Event::fake([SlaBreached::class, SlaNearingBreach::class]);

        $county = County::factory()->create();

        // Issue created 30 min ago, due in 4h → total 4.5h, 30 min elapsed ≈ 11%
        Issue::factory()->for($county, 'county')->create([
            'status' => 'in_progress',
            'sla_breached' => false,
            'created_at' => now()->subMinutes(30),
            'sla_due_at' => now()->addHours(4),
            'asset_id' => null,
        ]);

        (new SlaService)->runSlaCheck();

        Event::assertNotDispatched(SlaNearingBreach::class);
    });

    it('does not fire SlaNearingBreach for already breached issues', function () {
        Event::fake([SlaNearingBreach::class]);

        $county = County::factory()->create();
        Issue::factory()->for($county, 'county')->create([
            'status' => 'in_progress',
            'sla_breached' => true,
            'created_at' => now()->subHours(3),
            'sla_due_at' => now()->subHour(), // past due
            'asset_id' => null,
        ]);

        (new SlaService)->runSlaCheck();

        Event::assertNotDispatched(SlaNearingBreach::class);
    });
});
