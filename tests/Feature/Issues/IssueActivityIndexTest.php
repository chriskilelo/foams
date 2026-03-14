<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

// ─── Helpers ─────────────────────────────────────────────────────────────────

function activityIndexUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

// ─── Index ───────────────────────────────────────────────────────────────────

describe('issue activities index', function () {
    it('admin can view the global issue activities feed', function () {
        $this->actingAs(activityIndexUser('admin'))
            ->get(route('issue-activities.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('IssueActivities/Index')
                ->has('activities')
                ->has('filters')
            );
    });

    it('noc can view the global issue activities feed', function () {
        $this->actingAs(activityIndexUser('noc'))
            ->get(route('issue-activities.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('IssueActivities/Index'));
    });

    it('director can view the global issue activities feed', function () {
        $this->actingAs(activityIndexUser('director'))
            ->get(route('issue-activities.index'))
            ->assertOk();
    });

    it('public_servant gets 403 on the activities feed', function () {
        $this->actingAs(activityIndexUser('public_servant'))
            ->get(route('issue-activities.index'))
            ->assertForbidden();
    });

    it('guest is redirected to login', function () {
        $this->get(route('issue-activities.index'))
            ->assertRedirect(route('login'));
    });

    it('can filter by action_type', function () {
        $this->actingAs(activityIndexUser('admin'))
            ->get(route('issue-activities.index', ['action_type' => 'comment']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.action_type', 'comment')
            );
    });
});
