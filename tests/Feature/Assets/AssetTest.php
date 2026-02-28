<?php

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\County;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

// Clear any RegionScope global scopes registered during requests so they
// cannot bleed into subsequent tests.
afterEach(fn () => clearRegionScope());

// ─── Helpers ──────────────────────────────────────────────────────────────────

function adminForAsset(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function rictoForAsset(Region $region): User
{
    $user = User::factory()->create(['region_id' => $region->id]);
    $user->assignRole('ricto');

    return $user;
}

function ictoForAsset(Region $region): User
{
    $user = User::factory()->create(['region_id' => $region->id]);
    $user->assignRole('icto');

    return $user;
}

// ─── Region Scoping ───────────────────────────────────────────────────────────

describe('assets region scoping', function () {
    it('admin sees all assets across all regions', function () {
        [$regionA, $regionB] = Region::factory()->count(2)->create();
        $countyA = County::factory()->for($regionA)->create();
        $countyB = County::factory()->for($regionB)->create();

        Asset::factory()->for($countyA, 'county')->create();
        Asset::factory()->for($countyB, 'county')->create();

        $this->actingAs(adminForAsset())
            ->get(route('assets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Assets/Index')
                ->where('assets.total', 2)
            );
    });

    it('ricto sees only assets in their region', function () {
        [$regionA, $regionB] = Region::factory()->count(2)->create();
        $countyA = County::factory()->for($regionA)->create();
        $countyB = County::factory()->for($regionB)->create();

        $assetA = Asset::factory()->for($countyA, 'county')->create();
        Asset::factory()->for($countyB, 'county')->create();

        $this->actingAs(rictoForAsset($regionA))
            ->get(route('assets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Assets/Index')
                ->where('assets.total', 1)
                ->where('assets.data.0.id', $assetA->id)
            );
    });

    it('icto sees only assets in their region', function () {
        [$regionA, $regionB] = Region::factory()->count(2)->create();
        $countyA = County::factory()->for($regionA)->create();
        $countyB = County::factory()->for($regionB)->create();

        $assetA = Asset::factory()->for($countyA, 'county')->create();
        Asset::factory()->for($countyB, 'county')->create();

        $this->actingAs(ictoForAsset($regionA))
            ->get(route('assets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Assets/Index')
                ->where('assets.total', 1)
                ->where('assets.data.0.id', $assetA->id)
            );
    });

    it('soft-deleted assets do not appear in the index', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();

        $active = Asset::factory()->for($county, 'county')->create();
        $deleted = Asset::factory()->for($county, 'county')->create();
        $deleted->delete();

        $this->actingAs(adminForAsset())
            ->get(route('assets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('assets.total', 1)
                ->where('assets.data.0.id', $active->id)
            );
    });
});

// ─── Asset Code Auto-Generation ───────────────────────────────────────────────

describe('asset code auto-generation', function () {
    it('generates the correct code format on creation', function () {
        $county = County::factory()->create(['code' => 'MSA']);

        $this->actingAs(adminForAsset())
            ->post(route('assets.store'), [
                'name' => 'Mombasa Hotspot',
                'type' => 'wifi_hotspot',
                'county_id' => $county->id,
                'location_name' => 'Town Square',
                'status' => 'operational',
            ])
            ->assertRedirect();

        $asset = Asset::where('county_id', $county->id)
            ->where('type', 'wifi_hotspot')
            ->firstOrFail();

        expect($asset->asset_code)->toBe('WIFI-MSA-001');
    });

    it('increments the sequence for the same county and type', function () {
        $county = County::factory()->create(['code' => 'NBI']);

        // Existing asset that occupies sequence 001.
        Asset::factory()->for($county, 'county')->create([
            'type' => 'nofbi_node',
            'asset_code' => 'NOFBI-NBI-001',
        ]);

        $admin = adminForAsset();

        $this->actingAs($admin)
            ->post(route('assets.store'), [
                'name' => 'Second Node',
                'type' => 'nofbi_node',
                'county_id' => $county->id,
                'location_name' => 'Data Centre',
                'status' => 'operational',
            ])
            ->assertRedirect();

        $created = Asset::where('county_id', $county->id)
            ->where('type', 'nofbi_node')
            ->where('name', 'Second Node')
            ->firstOrFail();

        expect($created->asset_code)->toBe('NOFBI-NBI-002');
    });

    it('sequences are independent per asset type', function () {
        $county = County::factory()->create(['code' => 'KSM']);
        $admin = adminForAsset();

        $this->actingAs($admin)->post(route('assets.store'), [
            'name' => 'Hotspot One',
            'type' => 'wifi_hotspot',
            'county_id' => $county->id,
            'location_name' => 'Market Square',
            'status' => 'operational',
        ]);

        $this->actingAs($admin)->post(route('assets.store'), [
            'name' => 'OGN One',
            'type' => 'ogn_equipment',
            'county_id' => $county->id,
            'location_name' => 'County HQ',
            'status' => 'operational',
        ]);

        expect(Asset::where('name', 'Hotspot One')->firstOrFail()->asset_code)->toBe('WIFI-KSM-001');
        expect(Asset::where('name', 'OGN One')->firstOrFail()->asset_code)->toBe('OGN-KSM-001');
    });

    it('uses the county code in uppercase', function () {
        $county = County::factory()->create(['code' => 'mba']);

        $this->actingAs(adminForAsset())
            ->post(route('assets.store'), [
                'name' => 'Test Node',
                'type' => 'nofbi_node',
                'county_id' => $county->id,
                'location_name' => 'HQ',
                'status' => 'operational',
            ])
            ->assertRedirect();

        $asset = Asset::where('county_id', $county->id)->firstOrFail();
        expect($asset->asset_code)->toBe('NOFBI-MBA-001');
    });
});

// ─── Index ────────────────────────────────────────────────────────────────────

describe('assets index', function () {
    it('guest is redirected to login', function () {
        $this->get(route('assets.index'))
            ->assertRedirect(route('login'));
    });

    it('admin can view the index', function () {
        $this->actingAs(adminForAsset())
            ->get(route('assets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Assets/Index'));
    });

    it('ricto can view the index', function () {
        $region = Region::factory()->create();

        $this->actingAs(rictoForAsset($region))
            ->get(route('assets.index'))
            ->assertOk();
    });

    it('icto can view the index', function () {
        $region = Region::factory()->create();

        $this->actingAs(ictoForAsset($region))
            ->get(route('assets.index'))
            ->assertOk();
    });
});

// ─── Show ─────────────────────────────────────────────────────────────────────

describe('assets show', function () {
    it('guest is redirected to login', function () {
        $asset = Asset::factory()->create();

        $this->get(route('assets.show', $asset))
            ->assertRedirect(route('login'));
    });

    it('admin can view an asset', function () {
        $asset = Asset::factory()->create();

        $this->actingAs(adminForAsset())
            ->get(route('assets.show', $asset))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Assets/Show')
                ->where('asset.id', $asset->id)
            );
    });

    it('ricto can view an asset in their region', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();

        $this->actingAs(rictoForAsset($region))
            ->get(route('assets.show', $asset))
            ->assertOk();
    });

    it('icto can view an asset in their region', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();

        $this->actingAs(ictoForAsset($region))
            ->get(route('assets.show', $asset))
            ->assertOk();
    });
});

// ─── Create / Store ───────────────────────────────────────────────────────────

describe('assets create and store', function () {
    it('admin can view the create form', function () {
        $this->actingAs(adminForAsset())
            ->get(route('assets.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Assets/Create'));
    });

    it('ricto gets 403 on the create form', function () {
        $region = Region::factory()->create();

        $this->actingAs(rictoForAsset($region))
            ->get(route('assets.create'))
            ->assertForbidden();
    });

    it('icto gets 403 on the create form', function () {
        $region = Region::factory()->create();

        $this->actingAs(ictoForAsset($region))
            ->get(route('assets.create'))
            ->assertForbidden();
    });

    it('admin can store a valid asset', function () {
        $county = County::factory()->create(['code' => 'TST']);

        $this->actingAs(adminForAsset())
            ->post(route('assets.store'), [
                'name' => 'New Hotspot',
                'type' => 'wifi_hotspot',
                'county_id' => $county->id,
                'location_name' => 'Test Location',
                'status' => 'operational',
            ])
            ->assertRedirect();

        expect(Asset::where('name', 'New Hotspot')->exists())->toBeTrue();
    });

    it('store fails validation on missing required fields', function () {
        $this->actingAs(adminForAsset())
            ->post(route('assets.store'), [])
            ->assertSessionHasErrors(['name', 'type', 'county_id', 'location_name', 'status']);
    });

    it('store fails validation on duplicate serial number', function () {
        $county = County::factory()->create();
        Asset::factory()->for($county, 'county')->create(['serial_number' => 'SN-DUPLICATE']);

        $this->actingAs(adminForAsset())
            ->post(route('assets.store'), [
                'name' => 'Another Asset',
                'type' => 'wifi_hotspot',
                'county_id' => $county->id,
                'location_name' => 'Somewhere',
                'status' => 'operational',
                'serial_number' => 'SN-DUPLICATE',
            ])
            ->assertSessionHasErrors('serial_number');
    });

    it('ricto gets 403 on store', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();

        $this->actingAs(rictoForAsset($region))
            ->post(route('assets.store'), [
                'name' => 'Unauthorised Asset',
                'type' => 'wifi_hotspot',
                'county_id' => $county->id,
                'location_name' => 'Somewhere',
                'status' => 'operational',
            ])
            ->assertForbidden();
    });
});

// ─── Edit / Update ────────────────────────────────────────────────────────────

describe('assets edit and update', function () {
    it('admin can view the edit form', function () {
        $asset = Asset::factory()->create();

        $this->actingAs(adminForAsset())
            ->get(route('assets.edit', $asset))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Assets/Edit')
                ->where('asset.id', $asset->id)
            );
    });

    it('ricto can view the edit form for an asset in their region', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();

        $this->actingAs(rictoForAsset($region))
            ->get(route('assets.edit', $asset))
            ->assertOk();
    });

    it('icto gets 403 on the edit form', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();

        $this->actingAs(ictoForAsset($region))
            ->get(route('assets.edit', $asset))
            ->assertForbidden();
    });

    it('admin can update an asset', function () {
        $county = County::factory()->create();
        $asset = Asset::factory()->for($county, 'county')->create(['name' => 'Old Name']);

        $this->actingAs(adminForAsset())
            ->put(route('assets.update', $asset), [
                'name' => 'New Name',
                'type' => $asset->type->value,
                'county_id' => $county->id,
                'location_name' => 'New Location',
                'status' => 'degraded',
            ])
            ->assertRedirect(route('assets.show', $asset));

        $asset->refresh();

        expect($asset->name)->toBe('New Name');
        expect($asset->status)->toBe(AssetStatus::Degraded);
    });

    it('ricto can update an asset in their region', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create(['name' => 'Original']);

        $this->actingAs(rictoForAsset($region))
            ->put(route('assets.update', $asset), [
                'name' => 'Updated By RICTO',
                'type' => $asset->type->value,
                'county_id' => $county->id,
                'location_name' => $asset->location_name,
                'status' => $asset->status->value,
            ])
            ->assertRedirect();

        expect($asset->fresh()->name)->toBe('Updated By RICTO');
    });

    it('icto gets 403 on update', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();

        $this->actingAs(ictoForAsset($region))
            ->put(route('assets.update', $asset), [
                'name' => 'Hack',
                'type' => $asset->type->value,
                'county_id' => $county->id,
                'location_name' => $asset->location_name,
                'status' => $asset->status->value,
            ])
            ->assertForbidden();
    });

    it('update fails validation on missing required fields', function () {
        $asset = Asset::factory()->create();

        $this->actingAs(adminForAsset())
            ->put(route('assets.update', $asset), [])
            ->assertSessionHasErrors(['name', 'type', 'county_id', 'location_name', 'status']);
    });
});

// ─── Destroy ──────────────────────────────────────────────────────────────────

describe('assets destroy', function () {
    it('admin can soft-delete an asset', function () {
        $asset = Asset::factory()->create();

        $this->actingAs(adminForAsset())
            ->delete(route('assets.destroy', $asset))
            ->assertRedirect(route('assets.index'));

        expect(Asset::find($asset->id))->toBeNull();
        expect(Asset::withTrashed()->find($asset->id))->not->toBeNull();
    });

    it('soft-deleted asset is not visible in the index', function () {
        $asset = Asset::factory()->create();
        $asset->delete();

        $this->actingAs(adminForAsset())
            ->get(route('assets.index'))
            ->assertInertia(fn ($page) => $page
                ->where('assets.total', 0)
            );
    });

    it('ricto gets 403 on destroy', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();

        $this->actingAs(rictoForAsset($region))
            ->delete(route('assets.destroy', $asset))
            ->assertForbidden();
    });

    it('guest is redirected to login on destroy', function () {
        $asset = Asset::factory()->create();

        $this->delete(route('assets.destroy', $asset))
            ->assertRedirect(route('login'));
    });
});
