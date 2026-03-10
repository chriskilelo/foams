<?php

use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Jobs\SendEscalationNotification;
use App\Jobs\SendIssueAcknowledgementEmail;
use App\Jobs\SendSlaBreachNotification;
use App\Jobs\SendSmsJob;
use App\Jobs\SendStatusChangeEmail;
use App\Mail\EscalationMail;
use App\Mail\IssueAcknowledgedMail;
use App\Mail\SlaBreachMail;
use App\Models\County;
use App\Models\Issue;
use App\Models\Region;
use App\Models\SlaConfiguration;
use App\Models\User;
use App\Services\IssueService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => $this->seed(RoleSeeder::class));

// ─── IssueService job dispatch ──────────────────────────────────────────────

describe('IssueService notification dispatch', function () {
    it('dispatches SendIssueAcknowledgementEmail for general_public reporter', function () {
        Bus::fake();

        $county = County::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        SlaConfiguration::factory()->forSeverity(IssueSeverity::Low)->create();

        app(IssueService::class)->createIssue([
            'county_id' => $county->id,
            'issue_type' => 'connectivity',
            'severity' => 'low',
            'reporter_category' => 'general_public',
            'reporter_name' => 'Jane Wanjiku',
            'reporter_email' => 'jane@example.com',
            'description' => 'Internet is down in the public library area.',
            'asset_id' => null,
        ], null);

        Bus::assertDispatched(SendIssueAcknowledgementEmail::class);
    });

    it('dispatches SendIssueAcknowledgementEmail for public_servant reporter', function () {
        Bus::fake();

        $county = County::factory()->create();
        SlaConfiguration::factory()->forSeverity(IssueSeverity::Medium)->create();

        app(IssueService::class)->createIssue([
            'county_id' => $county->id,
            'issue_type' => 'hardware_failure',
            'severity' => 'medium',
            'reporter_category' => 'public_servant',
            'reporter_name' => 'John Otieno',
            'reporter_email' => 'john.otieno@gov.go.ke',
            'description' => 'Network equipment failure at the county offices.',
            'asset_id' => null,
        ], null);

        Bus::assertDispatched(SendIssueAcknowledgementEmail::class);
    });

    it('does not dispatch SendIssueAcknowledgementEmail for field_officer reporter', function () {
        Bus::fake();

        $county = County::factory()->create();
        $noc = User::factory()->create();
        $noc->assignRole('noc');
        SlaConfiguration::factory()->forSeverity(IssueSeverity::Low)->create();

        app(IssueService::class)->createIssue([
            'county_id' => $county->id,
            'issue_type' => 'connectivity',
            'severity' => 'low',
            'reporter_category' => 'field_officer',
            'description' => 'Field officer logged this issue.',
            'asset_id' => null,
        ], $noc);

        Bus::assertNotDispatched(SendIssueAcknowledgementEmail::class);
    });

    it('dispatches SendSmsJob for critical issue when RICTO has a phone number', function () {
        Bus::fake();

        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();

        $ricto = User::factory()->create(['region_id' => $region->id, 'phone' => '+254712345678']);
        $ricto->assignRole('ricto');

        SlaConfiguration::factory()->forSeverity(IssueSeverity::Critical)->create();

        app(IssueService::class)->createIssue([
            'county_id' => $county->id,
            'issue_type' => 'outage',
            'severity' => 'critical',
            'reporter_category' => 'field_officer',
            'description' => 'Complete outage affecting all systems in the county.',
            'asset_id' => null,
        ], $ricto);

        Bus::assertDispatched(SendSmsJob::class, fn ($job) => $job->ricto->id === $ricto->id);
    });

    it('does not dispatch SendSmsJob when issue severity is not critical', function () {
        Bus::fake();

        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();

        $ricto = User::factory()->create(['region_id' => $region->id, 'phone' => '+254712345678']);
        $ricto->assignRole('ricto');

        SlaConfiguration::factory()->forSeverity(IssueSeverity::High)->create();

        app(IssueService::class)->createIssue([
            'county_id' => $county->id,
            'issue_type' => 'degraded_service',
            'severity' => 'high',
            'reporter_category' => 'field_officer',
            'description' => 'Degraded connectivity in the area affecting multiple users.',
            'asset_id' => null,
        ], $ricto);

        Bus::assertNotDispatched(SendSmsJob::class);
    });

    it('does not dispatch SendSmsJob for critical issue when RICTO has no phone', function () {
        Bus::fake();

        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();

        $ricto = User::factory()->create(['region_id' => $region->id, 'phone' => null]);
        $ricto->assignRole('ricto');

        SlaConfiguration::factory()->forSeverity(IssueSeverity::Critical)->create();

        app(IssueService::class)->createIssue([
            'county_id' => $county->id,
            'issue_type' => 'outage',
            'severity' => 'critical',
            'reporter_category' => 'field_officer',
            'description' => 'Complete outage affecting all systems in the county.',
            'asset_id' => null,
        ], $ricto);

        Bus::assertNotDispatched(SendSmsJob::class);
    });

    it('dispatches SendEscalationNotification when issue is escalated', function () {
        Bus::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'status' => IssueStatus::InProgress,
        ]);
        $ricto = User::factory()->create();
        $ricto->assignRole('ricto');

        app(IssueService::class)->escalate($issue, $ricto, 'Needs director attention urgently.');

        Bus::assertDispatched(SendEscalationNotification::class, function ($job) use ($issue) {
            return $job->issue->id === $issue->id
                && $job->reason === 'Needs director attention urgently.';
        });
    });

    it('dispatches SendStatusChangeEmail on status transition', function () {
        Bus::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'status' => IssueStatus::New,
            'reporter_email' => 'reporter@example.com',
            'reporter_category' => 'general_public',
        ]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');

        app(IssueService::class)->transitionStatus($issue, 'acknowledged', $noc, 'Issue acknowledged.');

        Bus::assertDispatched(SendStatusChangeEmail::class);
    });
});

// ─── SendIssueAcknowledgementEmail job ──────────────────────────────────────

describe('SendIssueAcknowledgementEmail job', function () {
    it('sends IssueAcknowledgedMail to reporter email', function () {
        Mail::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'reporter_category' => 'general_public',
            'reporter_email' => 'public@example.com',
            'reporter_name' => 'Public User',
        ]);

        (new \App\Jobs\SendIssueAcknowledgementEmail($issue))->handle();

        Mail::assertSent(IssueAcknowledgedMail::class, fn ($mail) => $mail->hasTo('public@example.com'));
    });

    it('does not send mail for field_officer reporter category', function () {
        Mail::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'reporter_category' => 'field_officer',
            'reporter_email' => 'officer@icta.go.ke',
        ]);

        (new \App\Jobs\SendIssueAcknowledgementEmail($issue))->handle();

        Mail::assertNothingSent();
    });

    it('does not send mail when reporter has no email', function () {
        Mail::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'reporter_category' => 'general_public',
            'reporter_email' => null,
        ]);

        (new \App\Jobs\SendIssueAcknowledgementEmail($issue))->handle();

        Mail::assertNothingSent();
    });
});

// ─── SendEscalationNotification job ─────────────────────────────────────────

describe('SendEscalationNotification job', function () {
    it('sends EscalationMail to all active directors', function () {
        Mail::fake();

        $director1 = User::factory()->create(['email' => 'dir1@icta.go.ke', 'is_active' => true]);
        $director1->assignRole('director');
        $director2 = User::factory()->create(['email' => 'dir2@icta.go.ke', 'is_active' => true]);
        $director2->assignRole('director');

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'is_escalated' => true,
        ]);

        (new SendEscalationNotification($issue, 'Needs immediate director action.'))->handle();

        Mail::assertSent(EscalationMail::class, 2);
        Mail::assertSent(EscalationMail::class, fn ($mail) => $mail->hasTo('dir1@icta.go.ke'));
        Mail::assertSent(EscalationMail::class, fn ($mail) => $mail->hasTo('dir2@icta.go.ke'));
    });
});

// ─── SendSlaBreachNotification job ──────────────────────────────────────────

describe('SendSlaBreachNotification job', function () {
    it('sends SlaBreachMail to NOC officers and RICTO for the issue region', function () {
        Mail::fake();

        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();

        $noc = User::factory()->create(['email' => 'noc@icta.go.ke', 'is_active' => true]);
        $noc->assignRole('noc');

        $ricto = User::factory()->create([
            'email' => 'ricto@icta.go.ke',
            'is_active' => true,
            'region_id' => $region->id,
        ]);
        $ricto->assignRole('ricto');

        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'sla_breached' => true,
        ]);

        (new SendSlaBreachNotification($issue))->handle();

        Mail::assertSent(SlaBreachMail::class, 2);
        Mail::assertSent(SlaBreachMail::class, fn ($mail) => $mail->hasTo('noc@icta.go.ke'));
        Mail::assertSent(SlaBreachMail::class, fn ($mail) => $mail->hasTo('ricto@icta.go.ke'));
    });
});

// ─── SendSmsJob local environment logging ───────────────────────────────────

describe('SendSmsJob', function () {
    it('logs SMS content instead of sending in local environment', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();

        $ricto = User::factory()->create(['phone' => '+254711000001', 'region_id' => $region->id]);
        $ricto->assignRole('ricto');

        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'severity' => 'critical',
        ]);

        // Should not throw — in local/testing environment it only logs
        expect(fn () => (new SendSmsJob($issue, $ricto))->handle())->not->toThrow(Throwable::class);
    });
});
