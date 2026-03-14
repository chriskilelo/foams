<?php

use App\Events\IssueAssigned;
use App\Jobs\SendAssignmentNotification;
use App\Models\County;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\Region;
use App\Models\User;
use App\Services\IssueService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

// ─── IssueService::assignIssue ────────────────────────────────────────────────

describe('IssueService::assignIssue', function () {
    beforeEach(fn () => $this->seed(RoleSeeder::class));

    it('assigns an issue to an ICTO and creates an internal activity record', function () {
        Event::fake();
        Bus::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $icto = User::factory()->create();
        $icto->assignRole('icto');

        $service = app(IssueService::class);
        $service->assignIssue($issue, $icto->id, $noc);

        $issue->refresh();
        expect($issue->assigned_to_user_id)->toBe($icto->id);

        $activity = IssueActivity::where('issue_id', $issue->id)
            ->where('action_type', 'assignment')
            ->latest()
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->is_internal)->toBeTrue()
            ->and($activity->user_id)->toBe($noc->id)
            ->and($activity->comment)->toContain($icto->name);
    });

    it('dispatches IssueAssigned event and SendAssignmentNotification job on assignment', function () {
        Event::fake();
        Bus::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $icto = User::factory()->create();
        $icto->assignRole('icto');

        $service = app(IssueService::class);
        $service->assignIssue($issue, $icto->id, $noc);

        Event::assertDispatched(IssueAssigned::class, fn ($e) => $e->assignee->id === $icto->id);
        Bus::assertDispatched(SendAssignmentNotification::class, fn ($j) => $j->assignee->id === $icto->id);
    });

    it('records a reassignment comment when replacing an existing assignee', function () {
        Event::fake();
        Bus::fake();

        $county = County::factory()->create();
        $icto1 = User::factory()->create(['name' => 'Alice ICTO']);
        $icto1->assignRole('icto');
        $icto2 = User::factory()->create(['name' => 'Bob ICTO']);
        $icto2->assignRole('icto');

        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'assigned_to_user_id' => $icto1->id,
        ]);

        $noc = User::factory()->create();
        $noc->assignRole('noc');

        $service = app(IssueService::class);
        $service->assignIssue($issue, $icto2->id, $noc);

        $activity = IssueActivity::where('issue_id', $issue->id)
            ->where('action_type', 'assignment')
            ->latest()
            ->first();

        expect($activity->comment)
            ->toContain('Alice ICTO')
            ->toContain('Bob ICTO');
    });

    it('removes an assignment when assignee_id is null and records activity', function () {
        Event::fake();
        Bus::fake();

        $county = County::factory()->create();
        $icto = User::factory()->create();
        $icto->assignRole('icto');
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'assigned_to_user_id' => $icto->id,
        ]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');

        $service = app(IssueService::class);
        $service->assignIssue($issue, null, $noc);

        $issue->refresh();
        expect($issue->assigned_to_user_id)->toBeNull();

        $activity = IssueActivity::where('issue_id', $issue->id)
            ->where('action_type', 'assignment')
            ->latest()
            ->first();

        expect($activity->comment)->toBe('Assignment removed.');
        Bus::assertNotDispatched(SendAssignmentNotification::class);
    });
});

// ─── IssuePolicy::assign ──────────────────────────────────────────────────────

describe('IssuePolicy::assign', function () {
    beforeEach(fn () => $this->seed(RoleSeeder::class));

    it('NOC can assign issues', function () {
        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        expect($noc->can('assign', $issue))->toBeTrue();
    });

    it('RICTO can assign issues', function () {
        $ricto = User::factory()->create();
        $ricto->assignRole('ricto');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        expect($ricto->can('assign', $issue))->toBeTrue();
    });

    it('Director can assign issues', function () {
        $director = User::factory()->create();
        $director->assignRole('director');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        expect($director->can('assign', $issue))->toBeTrue();
    });

    it('ICTO cannot assign issues', function () {
        $icto = User::factory()->create();
        $icto->assignRole('icto');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        expect($icto->can('assign', $issue))->toBeFalse();
    });

    it('AICTO cannot assign issues', function () {
        $aicto = User::factory()->create();
        $aicto->assignRole('aicto');
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        expect($aicto->can('assign', $issue))->toBeFalse();
    });
});

// ─── PATCH /issues/{issue}/assign (HTTP) ──────────────────────────────────────

describe('PATCH /issues/{issue}/assign', function () {
    beforeEach(fn () => $this->seed(RoleSeeder::class));

    it('NOC can assign an issue to an ICTO', function () {
        Event::fake();
        Bus::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $icto = User::factory()->create();
        $icto->assignRole('icto');

        $this->actingAs($noc)
            ->patch("/issues/{$issue->id}/assign", ['assigned_to_user_id' => $icto->id])
            ->assertRedirect("/issues/{$issue->id}");

        $issue->refresh();
        expect($issue->assigned_to_user_id)->toBe($icto->id);
    });

    it('NOC can assign an issue to a NOC officer', function () {
        Event::fake();
        Bus::fake();

        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);
        $actor = User::factory()->create();
        $actor->assignRole('noc');
        $assignee = User::factory()->create();
        $assignee->assignRole('noc');

        $this->actingAs($actor)
            ->patch("/issues/{$issue->id}/assign", ['assigned_to_user_id' => $assignee->id])
            ->assertRedirect("/issues/{$issue->id}");

        $issue->refresh();
        expect($issue->assigned_to_user_id)->toBe($assignee->id);
    });

    it('NOC can unassign by passing null', function () {
        Event::fake();
        Bus::fake();

        $county = County::factory()->create();
        $icto = User::factory()->create();
        $icto->assignRole('icto');
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'assigned_to_user_id' => $icto->id,
        ]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');

        $this->actingAs($noc)
            ->patch("/issues/{$issue->id}/assign", ['assigned_to_user_id' => null])
            ->assertRedirect("/issues/{$issue->id}");

        $issue->refresh();
        expect($issue->assigned_to_user_id)->toBeNull();
    });

    it('RICTO can assign to an ICTO in the same region', function () {
        Event::fake();
        Bus::fake();

        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $ricto = User::factory()->create(['region_id' => $region->id]);
        $ricto->assignRole('ricto');
        $icto = User::factory()->create(['region_id' => $region->id]);
        $icto->assignRole('icto');
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        $this->actingAs($ricto)
            ->patch("/issues/{$issue->id}/assign", ['assigned_to_user_id' => $icto->id])
            ->assertRedirect("/issues/{$issue->id}");

        $issue->refresh();
        expect($issue->assigned_to_user_id)->toBe($icto->id);
    });

    it('RICTO cannot assign to an ICTO in a different region', function () {
        $region1 = Region::factory()->create();
        $region2 = Region::factory()->create();
        $county = County::factory()->for($region1)->create();

        $ricto = User::factory()->create(['region_id' => $region1->id]);
        $ricto->assignRole('ricto');

        $ictoOtherRegion = User::factory()->create(['region_id' => $region2->id]);
        $ictoOtherRegion->assignRole('icto');

        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        $this->actingAs($ricto)
            ->patch("/issues/{$issue->id}/assign", ['assigned_to_user_id' => $ictoOtherRegion->id])
            ->assertSessionHasErrors('assigned_to_user_id');
    });

    it('ICTO cannot assign issues — 403', function () {
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);
        $icto = User::factory()->create();
        $icto->assignRole('icto');
        $icto2 = User::factory()->create();
        $icto2->assignRole('icto');

        $this->actingAs($icto)
            ->patch("/issues/{$issue->id}/assign", ['assigned_to_user_id' => $icto2->id])
            ->assertForbidden();
    });

    it('cannot assign to an AICTO — validation failure', function () {
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $aicto = User::factory()->create();
        $aicto->assignRole('aicto');

        $this->actingAs($noc)
            ->patch("/issues/{$issue->id}/assign", ['assigned_to_user_id' => $aicto->id])
            ->assertSessionHasErrors('assigned_to_user_id');
    });

    it('cannot assign to an inactive user — validation failure', function () {
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');
        $icto = User::factory()->create(['is_active' => false]);
        $icto->assignRole('icto');

        $this->actingAs($noc)
            ->patch("/issues/{$issue->id}/assign", ['assigned_to_user_id' => $icto->id])
            ->assertSessionHasErrors('assigned_to_user_id');
    });

    it('unauthenticated users are redirected to login', function () {
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);

        $this->patch("/issues/{$issue->id}/assign", ['assigned_to_user_id' => 1])
            ->assertRedirect('/login');
    });

    it('show page exposes assignable_users and can.assign = true for NOC', function () {
        $county = County::factory()->create();
        $issue = Issue::factory()->for($county, 'county')->create(['asset_id' => null]);
        $noc = User::factory()->create();
        $noc->assignRole('noc');

        $this->actingAs($noc)
            ->get("/issues/{$issue->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Issues/Show')
                ->has('assignable_users')
                ->where('can.assign', true)
            );
    });

    it('show page exposes can.assign = false for ICTO', function () {
        $county = County::factory()->create();
        $icto = User::factory()->create();
        $icto->assignRole('icto');
        $issue = Issue::factory()->for($county, 'county')->create([
            'asset_id' => null,
            'assigned_to_user_id' => $icto->id,
        ]);

        $this->actingAs($icto)
            ->get("/issues/{$issue->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Issues/Show')
                ->where('can.assign', false)
            );
    });
});
