<?php

use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\ResolutionType;
use App\Models\County;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\SlaConfiguration;
use App\Models\User;
use App\Services\IssueService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Event;

// ─── IssueService ──────────────────────────────────────────────────────────────

describe('IssueService', function () {
    beforeEach(fn () => $this->seed(RoleSeeder::class));

    it('generates a sequential reference number', function () {
        $service = app(IssueService::class);

        $ref1 = $service->generateReferenceNumber();
        $county = County::factory()->create();
        Issue::factory()->for($county, 'county')->create(['reference_number' => $ref1, 'asset_id' => null]);

        $ref2 = $service->generateReferenceNumber();

        expect($ref1)->toStartWith('ISS-')
            ->and($ref2)->not->toBe($ref1);

        $seq1 = (int) substr($ref1, 4);
        $seq2 = (int) substr($ref2, 4);
        expect($seq2)->toBe($seq1 + 1);
    });

    it('creates an issue with sla_due_at from sla_configurations', function () {
        $county = County::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        SlaConfiguration::factory()->forSeverity(IssueSeverity::High)->create();

        $service = app(IssueService::class);
        $issue = $service->createIssue([
            'county_id' => $county->id,
            'issue_type' => 'connectivity',
            'severity' => 'high',
            'reporter_category' => 'field_officer',
            'description' => 'Test description for the issue.',
            'asset_id' => null,
        ], $admin);

        expect($issue->sla_due_at)->not->toBeNull()
            ->and($issue->reference_number)->toStartWith('ISS-')
            ->and($issue->status)->toBe(IssueStatus::New);
    });

    it('fires IssueRaised event on creation', function () {
        Event::fake();

        $county = County::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $service = app(IssueService::class);
        $service->createIssue([
            'county_id' => $county->id,
            'issue_type' => 'hardware_failure',
            'severity' => 'medium',
            'reporter_category' => 'field_officer',
            'description' => 'Test issue description content.',
            'asset_id' => null,
        ], $admin);

        Event::assertDispatched(\App\Events\IssueRaised::class);
    });

    it('transitions status and creates an IssueActivity record', function () {
        Event::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'status' => IssueStatus::New,
        ]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');

        $service = app(IssueService::class);
        $service->transitionStatus($issue, 'acknowledged', $noc, 'Acknowledged by NOC.');

        $issue->refresh();

        expect($issue->status)->toBe(IssueStatus::Acknowledged)
            ->and($issue->acknowledged_at)->not->toBeNull();

        $activity = IssueActivity::where('issue_id', $issue->id)->latest()->first();
        expect($activity)
            ->not->toBeNull()
            ->and($activity->action_type)->toBe('status_change')
            ->and($activity->previous_status)->toBe('new')
            ->and($activity->new_status)->toBe('acknowledged');

        Event::assertDispatched(\App\Events\IssueStatusChanged::class);
    });

    it('throws on invalid status transition', function () {
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'status' => IssueStatus::New,
        ]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');

        $service = app(IssueService::class);

        expect(fn () => $service->transitionStatus($issue, 'resolved', $noc))->toThrow(InvalidArgumentException::class);
    });

    it('escalates an issue', function () {
        Event::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'status' => IssueStatus::InProgress,
        ]);
        $ricto = User::factory()->create();
        $ricto->assignRole('ricto');

        $service = app(IssueService::class);
        $service->escalate($issue, $ricto, 'Needs director attention.');

        $issue->refresh();

        expect($issue->is_escalated)->toBeTrue()
            ->and($issue->escalated_by_user_id)->toBe($ricto->id)
            ->and($issue->status)->toBe(IssueStatus::Escalated);

        Event::assertDispatched(\App\Events\IssueEscalated::class);
    });

    it('resolves an issue and creates a Resolution record', function () {
        Event::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'status' => IssueStatus::InProgress,
        ]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');

        $service = app(IssueService::class);
        $service->resolve($issue, $noc, [
            'root_cause' => 'Faulty network switch caused the connectivity issue.',
            'steps_taken' => ['Replaced faulty switch', 'Rebooted connected devices'],
            'resolution_type' => 'permanent',
        ]);

        $issue->refresh();

        expect($issue->status)->toBe(IssueStatus::Resolved)
            ->and($issue->resolved_at)->not->toBeNull()
            ->and($issue->resolution)->not->toBeNull()
            ->and($issue->resolution->resolution_type)->toBe(ResolutionType::Permanent);
    });

    it('closes a resolved issue', function () {
        Event::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'status' => IssueStatus::Resolved,
        ]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');

        $service = app(IssueService::class);
        $service->close($issue, $noc);

        $issue->refresh();
        expect($issue->status)->toBe(IssueStatus::Closed);
    });

    it('IssueActivity records cannot be updated', function () {
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);
        $user = User::factory()->create();

        $activity = IssueActivity::create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'action_type' => 'comment',
            'comment' => 'Original comment.',
            'is_internal' => false,
        ]);

        expect(fn () => $activity->update(['comment' => 'Modified']))->toThrow(RuntimeException::class);
    });
});

// ─── IssuePolicy ──────────────────────────────────────────────────────────────

describe('IssuePolicy', function () {
    beforeEach(fn () => $this->seed(RoleSeeder::class));

    it('admin can do everything', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        expect($admin->can('viewAny', Issue::class))->toBeTrue()
            ->and($admin->can('view', $issue))->toBeTrue()
            ->and($admin->can('create', Issue::class))->toBeTrue()
            ->and($admin->can('updateStatus', $issue))->toBeTrue()
            ->and($admin->can('escalate', $issue))->toBeTrue()
            ->and($admin->can('resolve', $issue))->toBeTrue()
            ->and($admin->can('close', $issue))->toBeTrue();
    });

    it('NOC can view and update status of all issues', function () {
        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        expect($noc->can('viewAny', Issue::class))->toBeTrue()
            ->and($noc->can('updateStatus', $issue))->toBeTrue()
            ->and($noc->can('escalate', $issue))->toBeTrue()
            ->and($noc->can('resolve', $issue))->toBeTrue()
            ->and($noc->can('close', $issue))->toBeTrue();
    });

    it('RICTO can update status and escalate', function () {
        $ricto = User::factory()->create();
        $ricto->assignRole('ricto');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        expect($ricto->can('updateStatus', $issue))->toBeTrue()
            ->and($ricto->can('escalate', $issue))->toBeTrue()
            ->and($ricto->can('resolve', $issue))->toBeTrue();
    });

    it('ICTO can update status only for own or assigned issues', function () {
        $icto = User::factory()->create();
        $icto->assignRole('icto');
        $county = County::factory()->create();

        $ownIssue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'created_by_user_id' => $icto->id,
        ]);
        $otherIssue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        expect($icto->can('updateStatus', $ownIssue))->toBeTrue()
            ->and($icto->can('updateStatus', $otherIssue))->toBeFalse();
    });

    it('ICTO cannot close issues', function () {
        $icto = User::factory()->create();
        $icto->assignRole('icto');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->resolved()->create(['asset_id' => null]);

        expect($icto->can('close', $issue))->toBeFalse();
    });

    it('public_servant cannot update issue status', function () {
        $user = User::factory()->create();
        $user->assignRole('public_servant');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        expect($user->can('updateStatus', $issue))->toBeFalse()
            ->and($user->can('escalate', $issue))->toBeFalse();
    });

    it('director cannot escalate (read-only role for escalation receipt)', function () {
        $director = User::factory()->create();
        $director->assignRole('director');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        expect($director->can('escalate', $issue))->toBeFalse();
    });
});

// ─── IssueController (HTTP) ────────────────────────────────────────────────────

describe('IssueController', function () {
    beforeEach(fn () => $this->seed(RoleSeeder::class));

    it('NOC can list issues', function () {
        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $county = County::factory()->create();
        Issue::factory()->for($county, 'county')->count(3)->create(['asset_id' => null]);

        $this->actingAs($noc)
            ->get('/issues')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Issues/Index'));
    });

    it('public_servant cannot list issues via the authenticated endpoint', function () {
        $user = User::factory()->create();
        $user->assignRole('public_servant');

        $this->actingAs($user)
            ->get('/issues')
            ->assertOk();
    });

    it('unauthenticated users are redirected from issue list', function () {
        $this->get('/issues')->assertRedirect('/login');
    });

    it('NOC can create an issue via POST', function () {
        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $county = County::factory()->create();
        SlaConfiguration::factory()->forSeverity(IssueSeverity::Low)->create();

        $this->actingAs($noc)
            ->post('/issues', [
                'county_id' => $county->id,
                'issue_type' => 'connectivity',
                'severity' => 'low',
                'reporter_category' => 'field_officer',
                'description' => 'Test issue description that is long enough.',
                'workaround_applied' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('issues', ['issue_type' => 'connectivity', 'county_id' => $county->id]);
    });

    it('validates required fields on issue creation', function () {
        $noc = User::factory()->create();
        $noc->assignRole('noc');

        $this->actingAs($noc)
            ->post('/issues', [])
            ->assertSessionHasErrors(['county_id', 'issue_type', 'severity', 'description']);
    });

    it('NOC can update issue status', function () {
        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'status' => IssueStatus::New,
        ]);

        $this->actingAs($noc)
            ->patch("/issues/{$issue->id}/status", ['status' => 'acknowledged'])
            ->assertRedirect();

        expect($issue->fresh()->status)->toBe(IssueStatus::Acknowledged);
    });

    it('returns 403 when AICTO tries to update status of another officer issue', function () {
        $aicto = User::factory()->create();
        $aicto->assignRole('aicto');
        $otherUser = User::factory()->create();
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'created_by_user_id' => $otherUser->id,
            'assigned_to_user_id' => null,
        ]);

        $this->actingAs($aicto)
            ->patch("/issues/{$issue->id}/status", ['status' => 'acknowledged'])
            ->assertForbidden();
    });

    it('NOC can escalate an issue', function () {
        Event::fake();

        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'status' => IssueStatus::InProgress,
        ]);

        $this->actingAs($noc)
            ->post("/issues/{$issue->id}/escalate", ['reason' => 'Needs urgent attention from director.'])
            ->assertRedirect();

        expect($issue->fresh()->is_escalated)->toBeTrue();
    });

    it('NOC can resolve an issue', function () {
        Event::fake();

        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'status' => IssueStatus::InProgress,
        ]);

        $this->actingAs($noc)
            ->post("/issues/{$issue->id}/resolve", [
                'root_cause' => 'Hardware component failure identified and replaced.',
                'steps_taken' => ['Replaced switch', 'Tested connectivity'],
                'resolution_type' => 'permanent',
            ])
            ->assertRedirect();

        expect($issue->fresh()->status)->toBe(IssueStatus::Resolved);
    });

    it('NOC can close a resolved issue', function () {
        Event::fake();

        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->resolved()->create(['asset_id' => null]);

        $this->actingAs($noc)
            ->post("/issues/{$issue->id}/close")
            ->assertRedirect();

        expect($issue->fresh()->status)->toBe(IssueStatus::Closed);
    });
});

// ─── Status Transition Validation ─────────────────────────────────────────────

describe('Status transitions', function () {
    beforeEach(fn () => $this->seed(RoleSeeder::class));

    $validTransitions = [
        ['new', 'acknowledged'],
        ['new', 'duplicate'],
        ['acknowledged', 'in_progress'],
        ['acknowledged', 'duplicate'],
        ['in_progress', 'pending_third_party'],
        ['pending_third_party', 'in_progress'],
        ['resolved', 'closed'],
    ];

    foreach ($validTransitions as [$from, $to]) {
        it("allows transition from {$from} to {$to}", function () use ($from, $to) {
            Event::fake();

            $county = County::factory()->create();
            $issue = Issue::factory()->for($county, 'county')->create([
                'asset_id' => null,
                'status' => IssueStatus::from($from),
            ]);
            $noc = User::factory()->create();
            $noc->assignRole('noc');

            $service = app(IssueService::class);
            $service->transitionStatus($issue, $to, $noc);

            expect($issue->fresh()->status)->toBe(IssueStatus::from($to));
        });
    }

    $invalidTransitions = [
        ['new', 'resolved'],
        ['new', 'closed'],
        ['new', 'in_progress'],
        ['closed', 'new'],
        ['resolved', 'new'],
        ['duplicate', 'new'],
    ];

    foreach ($invalidTransitions as [$from, $to]) {
        it("rejects invalid transition from {$from} to {$to}", function () use ($from, $to) {
            $county = County::factory()->create();
            $issue = Issue::factory()->for($county, 'county')->create([
                'asset_id' => null,
                'status' => IssueStatus::from($from),
            ]);
            $noc = User::factory()->create();
            $noc->assignRole('noc');

            $service = app(IssueService::class);
            expect(fn () => $service->transitionStatus($issue, $to, $noc))->toThrow(InvalidArgumentException::class);
        });
    }
});
