<?php

use App\Models\County;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

// ─── Helpers ─────────────────────────────────────────────────────────────────

function adminForCounty(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function rictoForCounty(): User
{
    $user = User::factory()->create();
    $user->assignRole('ricto');

    return $user;
}

// ─── Index ───────────────────────────────────────────────────────────────────

describe('admin counties index', function () {
    it('admin can view the counties index', function () {
        $this->actingAs(adminForCounty())
            ->get(route('admin.counties.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Counties/Index'));
    });

    it('non-admin gets 403 on counties index', function () {
        $this->actingAs(rictoForCounty())
            ->get(route('admin.counties.index'))
            ->assertForbidden();
    });

    it('guest is redirected to login from counties index', function () {
        $this->get(route('admin.counties.index'))
            ->assertRedirect(route('login'));
    });
});

// ─── Store ───────────────────────────────────────────────────────────────────

describe('admin counties store', function () {
    it('admin can create a county', function () {
        $region = Region::factory()->create();

        $this->actingAs(adminForCounty())
            ->post(route('admin.counties.store'), [
                'name' => 'Nairobi City',
                'code' => 'NBI1',
                'region_id' => $region->id,
            ])
            ->assertRedirect(route('admin.counties.index'));

        expect(County::where('name', 'Nairobi City')->exists())->toBeTrue();
    });

    it('store fails validation on duplicate name', function () {
        $region = Region::factory()->create();
        County::factory()->for($region)->create(['name' => 'Mombasa', 'code' => 'MSA1']);

        $this->actingAs(adminForCounty())
            ->post(route('admin.counties.store'), [
                'name' => 'Mombasa',
                'code' => 'MSA2',
                'region_id' => $region->id,
            ])
            ->assertSessionHasErrors('name');
    });

    it('store fails validation when region_id is missing', function () {
        $this->actingAs(adminForCounty())
            ->post(route('admin.counties.store'), [
                'name' => 'Kisumu',
                'code' => 'KSM1',
            ])
            ->assertSessionHasErrors('region_id');
    });

    it('store fails validation when region_id does not exist', function () {
        $this->actingAs(adminForCounty())
            ->post(route('admin.counties.store'), [
                'name' => 'Kisumu',
                'code' => 'KSM1',
                'region_id' => 99999,
            ])
            ->assertSessionHasErrors('region_id');
    });

    it('non-admin gets 403 on store', function () {
        $region = Region::factory()->create();

        $this->actingAs(rictoForCounty())
            ->post(route('admin.counties.store'), [
                'name' => 'Kisumu',
                'code' => 'KSM1',
                'region_id' => $region->id,
            ])
            ->assertForbidden();
    });
});

// ─── Edit ────────────────────────────────────────────────────────────────────

describe('admin counties edit', function () {
    it('admin can view the edit form', function () {
        $county = County::factory()->create();

        $this->actingAs(adminForCounty())
            ->get(route('admin.counties.edit', $county))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Counties/Edit')
                ->where('county.id', $county->id)
            );
    });

    it('non-admin gets 403 on edit form', function () {
        $county = County::factory()->create();

        $this->actingAs(rictoForCounty())
            ->get(route('admin.counties.edit', $county))
            ->assertForbidden();
    });
});

// ─── Update ──────────────────────────────────────────────────────────────────

describe('admin counties update', function () {
    it('admin can update a county', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create(['name' => 'Old County', 'code' => 'OLD1']);

        $newRegion = Region::factory()->create();

        $this->actingAs(adminForCounty())
            ->put(route('admin.counties.update', $county), [
                'name' => 'New County',
                'code' => 'NEW1',
                'region_id' => $newRegion->id,
            ])
            ->assertRedirect(route('admin.counties.index'));

        expect($county->fresh())
            ->name->toBe('New County')
            ->code->toBe('NEW1')
            ->region_id->toBe($newRegion->id);
    });

    it('update allows keeping the same name and code on same county', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create(['name' => 'Mombasa', 'code' => 'MSA1']);

        $this->actingAs(adminForCounty())
            ->put(route('admin.counties.update', $county), [
                'name' => 'Mombasa',
                'code' => 'MSA1',
                'region_id' => $region->id,
            ])
            ->assertRedirect(route('admin.counties.index'));
    });

    it('non-admin gets 403 on update', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();

        $this->actingAs(rictoForCounty())
            ->put(route('admin.counties.update', $county), [
                'name' => 'New County',
                'code' => 'NEW1',
                'region_id' => $region->id,
            ])
            ->assertForbidden();
    });
});

// ─── Destroy ─────────────────────────────────────────────────────────────────

describe('admin counties destroy', function () {
    it('admin can delete a county with no dependencies', function () {
        $county = County::factory()->create();

        $this->actingAs(adminForCounty())
            ->delete(route('admin.counties.destroy', $county))
            ->assertRedirect(route('admin.counties.index'));

        expect(County::find($county->id))->toBeNull();
    });

    it('non-admin gets 403 on destroy', function () {
        $county = County::factory()->create();

        $this->actingAs(rictoForCounty())
            ->delete(route('admin.counties.destroy', $county))
            ->assertForbidden();
    });
});
