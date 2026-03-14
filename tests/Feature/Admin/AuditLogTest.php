<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

// ─── Helpers ─────────────────────────────────────────────────────────────────

function auditAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function auditNonAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('ricto');

    return $user;
}

// Avoid triggering Issue→County→Region factory cascade by using a User as the auditable model.
function makeAuditLog(array $attrs = []): AuditLog
{
    $user = User::factory()->create();

    return AuditLog::factory()->create(array_merge([
        'auditable_type' => User::class,
        'auditable_id' => $user->id,
    ], $attrs));
}

// ─── Index ────────────────────────────────────────────────────────────────────

describe('admin audit-logs index', function () {
    it('admin can view the audit log index', function () {
        $u = User::factory()->create();
        AuditLog::factory()->count(3)->create(['auditable_type' => User::class, 'auditable_id' => $u->id]);

        $this->actingAs(auditAdminUser())
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/AuditLogs/Index')
                ->has('logs')
                ->has('filters')
                ->has('users')
                ->has('events')
                ->has('auditableTypes')
            );
    });

    it('non-admin gets 403 on audit log index', function () {
        $this->actingAs(auditNonAdminUser())
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    });

    it('guest is redirected to login from audit log index', function () {
        $this->get(route('admin.audit-logs.index'))
            ->assertRedirect(route('login'));
    });
});

// ─── Filtering ────────────────────────────────────────────────────────────────

describe('admin audit-logs filtering', function () {
    it('filters by event', function () {
        makeAuditLog(['event' => 'user.created']);
        makeAuditLog(['event' => 'region.deleted']);

        $this->actingAs(auditAdminUser())
            ->get(route('admin.audit-logs.index', ['event' => 'user.created']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/AuditLogs/Index')
                ->where('logs.total', 1)
                ->where('logs.data.0.event', 'user.created')
            );
    });

    it('filters by user_id', function () {
        $admin = auditAdminUser();
        $otherUser = auditNonAdminUser();

        makeAuditLog(['user_id' => $admin->id, 'event' => 'region.created']);
        makeAuditLog(['user_id' => $otherUser->id, 'event' => 'region.deleted']);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['user_id' => $admin->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.event', 'region.created')
            );
    });

    it('filters by auditable_type', function () {
        makeAuditLog(['auditable_type' => \App\Models\User::class, 'event' => 'user.updated']);
        makeAuditLog(['auditable_type' => \App\Models\Region::class, 'event' => 'region.updated']);

        $this->actingAs(auditAdminUser())
            ->get(route('admin.audit-logs.index', ['auditable_type' => \App\Models\User::class]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.event', 'user.updated')
            );
    });

    it('filters by date range', function () {
        makeAuditLog(['created_at' => '2026-01-10 10:00:00', 'event' => 'old.event']);
        makeAuditLog(['created_at' => '2026-03-01 10:00:00', 'event' => 'new.event']);

        $this->actingAs(auditAdminUser())
            ->get(route('admin.audit-logs.index', ['date_from' => '2026-02-01', 'date_to' => '2026-03-31']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.event', 'new.event')
            );
    });

    it('filters by search term matching event', function () {
        makeAuditLog(['event' => 'sla_configuration.created']);
        makeAuditLog(['event' => 'user.deactivated']);

        $this->actingAs(auditAdminUser())
            ->get(route('admin.audit-logs.index', ['search' => 'sla_configuration']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.event', 'sla_configuration.created')
            );
    });

    it('filters by ip_address', function () {
        makeAuditLog(['ip_address' => '10.0.0.1', 'event' => 'match.event']);
        makeAuditLog(['ip_address' => '192.168.1.5', 'event' => 'other.event']);

        $this->actingAs(auditAdminUser())
            ->get(route('admin.audit-logs.index', ['ip_address' => '10.0.0']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.event', 'match.event')
            );
    });

    it('returns all entries when no filters applied', function () {
        $u = User::factory()->create();
        AuditLog::factory()->count(5)->create(['auditable_type' => User::class, 'auditable_id' => $u->id]);

        $this->actingAs(auditAdminUser())
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('logs.total', 5));
    });
});

// ─── Export CSV ───────────────────────────────────────────────────────────────

describe('admin audit-logs export csv', function () {
    it('admin can download csv export', function () {
        $u = User::factory()->create();
        AuditLog::factory()->count(2)->create(['auditable_type' => User::class, 'auditable_id' => $u->id]);

        $this->actingAs(auditAdminUser())
            ->get(route('admin.audit-logs.export.csv'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    });

    it('non-admin gets 403 on csv export', function () {
        $this->actingAs(auditNonAdminUser())
            ->get(route('admin.audit-logs.export.csv'))
            ->assertForbidden();
    });

    it('guest is redirected to login from csv export', function () {
        $this->get(route('admin.audit-logs.export.csv'))
            ->assertRedirect(route('login'));
    });
});

// ─── Export PDF ───────────────────────────────────────────────────────────────

describe('admin audit-logs export pdf', function () {
    it('admin can download pdf export', function () {
        $u = User::factory()->create();
        AuditLog::factory()->count(2)->create(['auditable_type' => User::class, 'auditable_id' => $u->id]);

        $this->actingAs(auditAdminUser())
            ->get(route('admin.audit-logs.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('non-admin gets 403 on pdf export', function () {
        $this->actingAs(auditNonAdminUser())
            ->get(route('admin.audit-logs.export.pdf'))
            ->assertForbidden();
    });

    it('guest is redirected to login from pdf export', function () {
        $this->get(route('admin.audit-logs.export.pdf'))
            ->assertRedirect(route('login'));
    });
});
