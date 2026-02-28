<?php

use App\Models\Region;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

// ─── Helpers ─────────────────────────────────────────────────────────────────

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function rictoUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('ricto');

    return $user;
}

// ─── Index ───────────────────────────────────────────────────────────────────

describe('admin regions index', function () {
    it('admin can view the regions index', function () {
        $this->actingAs(adminUser())
            ->get(route('admin.regions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Regions/Index'));
    });

    it('non-admin gets 403 on regions index', function () {
        $this->actingAs(rictoUser())
            ->get(route('admin.regions.index'))
            ->assertForbidden();
    });

    it('guest is redirected to login from regions index', function () {
        $this->get(route('admin.regions.index'))
            ->assertRedirect(route('login'));
    });
});

// ─── Create ──────────────────────────────────────────────────────────────────

describe('admin regions create', function () {
    it('admin can view the create form', function () {
        $this->actingAs(adminUser())
            ->get(route('admin.regions.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Regions/Create'));
    });

    it('non-admin gets 403 on create form', function () {
        $this->actingAs(rictoUser())
            ->get(route('admin.regions.create'))
            ->assertForbidden();
    });
});

// ─── Store ───────────────────────────────────────────────────────────────────

describe('admin regions store', function () {
    it('admin can create a region', function () {
        $this->actingAs(adminUser())
            ->post(route('admin.regions.store'), [
                'name' => 'Western',
                'code' => 'WST',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.regions.index'));

        expect(Region::where('name', 'Western')->exists())->toBeTrue();
    });

    it('store fails validation on duplicate name', function () {
        Region::factory()->create(['name' => 'Western', 'code' => 'WST']);

        $this->actingAs(adminUser())
            ->post(route('admin.regions.store'), [
                'name' => 'Western',
                'code' => 'WST2',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('name');
    });

    it('store fails validation when name is missing', function () {
        $this->actingAs(adminUser())
            ->post(route('admin.regions.store'), [
                'code' => 'WST',
            ])
            ->assertSessionHasErrors('name');
    });

    it('non-admin gets 403 on store', function () {
        $this->actingAs(rictoUser())
            ->post(route('admin.regions.store'), [
                'name' => 'Western',
                'code' => 'WST',
                'is_active' => true,
            ])
            ->assertForbidden();
    });
});

// ─── Edit ────────────────────────────────────────────────────────────────────

describe('admin regions edit', function () {
    it('admin can view the edit form', function () {
        $region = Region::factory()->create();

        $this->actingAs(adminUser())
            ->get(route('admin.regions.edit', $region))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Regions/Edit')
                ->where('region.id', $region->id)
            );
    });

    it('non-admin gets 403 on edit form', function () {
        $region = Region::factory()->create();

        $this->actingAs(rictoUser())
            ->get(route('admin.regions.edit', $region))
            ->assertForbidden();
    });
});

// ─── Update ──────────────────────────────────────────────────────────────────

describe('admin regions update', function () {
    it('admin can update a region', function () {
        $region = Region::factory()->create(['name' => 'Old Name', 'code' => 'OLD']);

        $this->actingAs(adminUser())
            ->put(route('admin.regions.update', $region), [
                'name' => 'New Name',
                'code' => 'NEW',
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.regions.index'));

        expect($region->fresh())
            ->name->toBe('New Name')
            ->code->toBe('NEW')
            ->is_active->toBeFalse();
    });

    it('update allows keeping the same name and code on same region', function () {
        $region = Region::factory()->create(['name' => 'Coast', 'code' => 'CST']);

        $this->actingAs(adminUser())
            ->put(route('admin.regions.update', $region), [
                'name' => 'Coast',
                'code' => 'CST',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.regions.index'));
    });

    it('non-admin gets 403 on update', function () {
        $region = Region::factory()->create();

        $this->actingAs(rictoUser())
            ->put(route('admin.regions.update', $region), [
                'name' => 'New Name',
                'code' => 'NEW',
            ])
            ->assertForbidden();
    });
});

// ─── Destroy ─────────────────────────────────────────────────────────────────

describe('admin regions destroy', function () {
    it('admin can soft-delete a region', function () {
        $region = Region::factory()->create();

        $this->actingAs(adminUser())
            ->delete(route('admin.regions.destroy', $region))
            ->assertRedirect(route('admin.regions.index'));

        expect(Region::find($region->id))->toBeNull();
        expect(Region::withTrashed()->find($region->id))->not->toBeNull();
    });

    it('deleted region is not visible in index', function () {
        $region = Region::factory()->create();
        $region->delete();

        $response = $this->actingAs(adminUser())
            ->get(route('admin.regions.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Regions/Index')
            ->where('regions', fn ($regions) => collect($regions)->every(
                fn ($r) => $r['id'] !== $region->id
            ))
        );
    });

    it('soft-deleted region data is preserved in the database', function () {
        $region = Region::factory()->create(['name' => 'Preserved Region']);
        $region->delete();

        $trashed = Region::withTrashed()->where('name', 'Preserved Region')->first();

        expect($trashed)->not->toBeNull();
        expect($trashed->deleted_at)->not->toBeNull();
    });

    it('non-admin gets 403 on destroy', function () {
        $region = Region::factory()->create();

        $this->actingAs(rictoUser())
            ->delete(route('admin.regions.destroy', $region))
            ->assertForbidden();
    });
});
