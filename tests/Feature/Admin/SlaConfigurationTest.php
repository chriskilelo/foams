<?php

use App\Enums\IssueSeverity;
use App\Models\SlaConfiguration;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

// ─── Helpers ─────────────────────────────────────────────────────────────────

function slaAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function slaNonAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('ricto');

    return $user;
}

// ─── Index ───────────────────────────────────────────────────────────────────

describe('admin sla-configurations index', function () {
    it('admin can view the sla configurations index', function () {
        SlaConfiguration::factory()->forSeverity(IssueSeverity::Critical)->create();

        $this->actingAs(slaAdminUser())
            ->get(route('admin.sla-configurations.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/SlaConfig/Index'));
    });

    it('non-admin gets 403 on sla configurations index', function () {
        $this->actingAs(slaNonAdminUser())
            ->get(route('admin.sla-configurations.index'))
            ->assertForbidden();
    });

    it('guest is redirected to login from sla configurations index', function () {
        $this->get(route('admin.sla-configurations.index'))
            ->assertRedirect(route('login'));
    });
});

// ─── Create ──────────────────────────────────────────────────────────────────

describe('admin sla-configurations create', function () {
    it('admin can view the create form', function () {
        $this->actingAs(slaAdminUser())
            ->get(route('admin.sla-configurations.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/SlaConfig/Create'));
    });

    it('non-admin gets 403 on create form', function () {
        $this->actingAs(slaNonAdminUser())
            ->get(route('admin.sla-configurations.create'))
            ->assertForbidden();
    });
});

// ─── Store ───────────────────────────────────────────────────────────────────

describe('admin sla-configurations store', function () {
    it('admin can create a sla configuration', function () {
        $this->actingAs(slaAdminUser())
            ->post(route('admin.sla-configurations.store'), [
                'severity' => 'high',
                'acknowledge_within_hrs' => 4,
                'resolve_within_hrs' => 8,
                'effective_from' => '2026-03-01 00:00:00',
            ])
            ->assertRedirect(route('admin.sla-configurations.index'));

        expect(
            SlaConfiguration::where('severity', 'high')
                ->where('acknowledge_within_hrs', 4)
                ->exists()
        )->toBeTrue();
    });

    it('store fails validation when severity is missing', function () {
        $this->actingAs(slaAdminUser())
            ->post(route('admin.sla-configurations.store'), [
                'acknowledge_within_hrs' => 4,
                'resolve_within_hrs' => 8,
                'effective_from' => '2026-03-01 00:00:00',
            ])
            ->assertSessionHasErrors('severity');
    });

    it('store fails validation when severity is invalid', function () {
        $this->actingAs(slaAdminUser())
            ->post(route('admin.sla-configurations.store'), [
                'severity' => 'unknown',
                'acknowledge_within_hrs' => 4,
                'resolve_within_hrs' => 8,
                'effective_from' => '2026-03-01 00:00:00',
            ])
            ->assertSessionHasErrors('severity');
    });

    it('store fails validation when resolve_within_hrs is less than acknowledge_within_hrs', function () {
        $this->actingAs(slaAdminUser())
            ->post(route('admin.sla-configurations.store'), [
                'severity' => 'medium',
                'acknowledge_within_hrs' => 10,
                'resolve_within_hrs' => 5,
                'effective_from' => '2026-03-01 00:00:00',
            ])
            ->assertSessionHasErrors('resolve_within_hrs');
    });

    it('store fails validation when effective_from is missing', function () {
        $this->actingAs(slaAdminUser())
            ->post(route('admin.sla-configurations.store'), [
                'severity' => 'low',
                'acknowledge_within_hrs' => 24,
                'resolve_within_hrs' => 72,
            ])
            ->assertSessionHasErrors('effective_from');
    });

    it('non-admin gets 403 on store', function () {
        $this->actingAs(slaNonAdminUser())
            ->post(route('admin.sla-configurations.store'), [
                'severity' => 'high',
                'acknowledge_within_hrs' => 4,
                'resolve_within_hrs' => 8,
                'effective_from' => '2026-03-01 00:00:00',
            ])
            ->assertForbidden();
    });

    it('store writes an audit log entry', function () {
        $admin = slaAdminUser();

        $this->actingAs($admin)
            ->post(route('admin.sla-configurations.store'), [
                'severity' => 'critical',
                'acknowledge_within_hrs' => 1,
                'resolve_within_hrs' => 4,
                'effective_from' => '2026-03-01 00:00:00',
            ]);

        expect(
            \App\Models\AuditLog::where('user_id', $admin->id)
                ->where('event', 'sla_configuration.created')
                ->exists()
        )->toBeTrue();
    });
});

// ─── Edit ────────────────────────────────────────────────────────────────────

describe('admin sla-configurations edit', function () {
    it('admin can view the edit form', function () {
        $config = SlaConfiguration::factory()->forSeverity(IssueSeverity::Low)->create();

        $this->actingAs(slaAdminUser())
            ->get(route('admin.sla-configurations.edit', $config))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/SlaConfig/Edit')
                ->where('configuration.id', $config->id)
            );
    });

    it('non-admin gets 403 on edit form', function () {
        $config = SlaConfiguration::factory()->forSeverity(IssueSeverity::Low)->create();

        $this->actingAs(slaNonAdminUser())
            ->get(route('admin.sla-configurations.edit', $config))
            ->assertForbidden();
    });
});

// ─── Update ──────────────────────────────────────────────────────────────────

describe('admin sla-configurations update', function () {
    it('admin can update a sla configuration', function () {
        $config = SlaConfiguration::factory()->forSeverity(IssueSeverity::Medium)->create([
            'acknowledge_within_hrs' => 8,
            'resolve_within_hrs' => 24,
        ]);

        $this->actingAs(slaAdminUser())
            ->put(route('admin.sla-configurations.update', $config), [
                'acknowledge_within_hrs' => 6,
                'resolve_within_hrs' => 12,
                'effective_from' => '2026-06-01 00:00:00',
            ])
            ->assertRedirect(route('admin.sla-configurations.index'));

        expect($config->fresh())
            ->acknowledge_within_hrs->toBe(6)
            ->resolve_within_hrs->toBe(12);
    });

    it('update fails validation when resolve_within_hrs is less than acknowledge_within_hrs', function () {
        $config = SlaConfiguration::factory()->forSeverity(IssueSeverity::High)->create();

        $this->actingAs(slaAdminUser())
            ->put(route('admin.sla-configurations.update', $config), [
                'acknowledge_within_hrs' => 8,
                'resolve_within_hrs' => 4,
                'effective_from' => '2026-06-01 00:00:00',
            ])
            ->assertSessionHasErrors('resolve_within_hrs');
    });

    it('non-admin gets 403 on update', function () {
        $config = SlaConfiguration::factory()->forSeverity(IssueSeverity::Critical)->create();

        $this->actingAs(slaNonAdminUser())
            ->put(route('admin.sla-configurations.update', $config), [
                'acknowledge_within_hrs' => 1,
                'resolve_within_hrs' => 4,
                'effective_from' => '2026-06-01 00:00:00',
            ])
            ->assertForbidden();
    });

    it('update writes an audit log entry', function () {
        $admin = slaAdminUser();
        $config = SlaConfiguration::factory()->forSeverity(IssueSeverity::Low)->create();

        $this->actingAs($admin)
            ->put(route('admin.sla-configurations.update', $config), [
                'acknowledge_within_hrs' => 24,
                'resolve_within_hrs' => 72,
                'effective_from' => '2026-06-01 00:00:00',
            ]);

        expect(
            \App\Models\AuditLog::where('user_id', $admin->id)
                ->where('event', 'sla_configuration.updated')
                ->exists()
        )->toBeTrue();
    });
});
