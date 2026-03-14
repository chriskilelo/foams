<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

// ─── Helpers ─────────────────────────────────────────────────────────────────

function resolutionIndexUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

// ─── Index ───────────────────────────────────────────────────────────────────

describe('resolutions index', function () {
    it('admin can view the resolutions index', function () {
        $this->actingAs(resolutionIndexUser('admin'))
            ->get(route('resolutions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Resolutions/Index')
                ->has('resolutions')
                ->has('filters')
            );
    });

    it('noc can view the resolutions index', function () {
        $this->actingAs(resolutionIndexUser('noc'))
            ->get(route('resolutions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Resolutions/Index'));
    });

    it('director can view the resolutions index', function () {
        $this->actingAs(resolutionIndexUser('director'))
            ->get(route('resolutions.index'))
            ->assertOk();
    });

    it('ricto can view the resolutions index', function () {
        $this->actingAs(resolutionIndexUser('ricto'))
            ->get(route('resolutions.index'))
            ->assertOk();
    });

    it('public_servant gets 403 on the resolutions index', function () {
        $this->actingAs(resolutionIndexUser('public_servant'))
            ->get(route('resolutions.index'))
            ->assertForbidden();
    });

    it('guest is redirected to login', function () {
        $this->get(route('resolutions.index'))
            ->assertRedirect(route('login'));
    });

    it('can filter by resolution type', function () {
        $this->actingAs(resolutionIndexUser('admin'))
            ->get(route('resolutions.index', ['type' => 'permanent']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.type', 'permanent')
            );
    });
});
