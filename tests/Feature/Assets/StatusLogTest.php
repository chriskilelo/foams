<?php

use App\Enums\AssetLogStatus;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\County;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

afterEach(fn () => clearRegionScope());

// ─── Helpers ──────────────────────────────────────────────────────────────────

function adminForLog(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function ictoForLog(Region $region): User
{
    $user = User::factory()->create(['region_id' => $region->id]);
    $user->assignRole('icto');

    return $user;
}

function aictoForLog(Region $region): User
{
    $user = User::factory()->create(['region_id' => $region->id]);
    $user->assignRole('aicto');

    return $user;
}

// ─── Index ────────────────────────────────────────────────────────────────────

describe('status log index', function () {
    it('icto can view their daily progress page', function () {
        $region = Region::factory()->create();

        $this->actingAs(ictoForLog($region))
            ->get(route('status-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Assets/StatusLog/Index'));
    });

    it('shows only assets assigned to the authenticated officer', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $icto = ictoForLog($region);

        $assignedAsset = Asset::factory()->for($county, 'county')->create(['assigned_to' => $icto->id]);
        Asset::factory()->for($county, 'county')->create(['assigned_to' => null]);

        $this->actingAs($icto)
            ->get(route('status-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('total_count', 1)
                ->where('assets.0.id', $assignedAsset->id)
            );
    });

    it('reflects correct logged and pending counts', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $icto = ictoForLog($region);

        $loggedAsset = Asset::factory()->for($county, 'county')->create(['assigned_to' => $icto->id]);
        $pendingAsset = Asset::factory()->for($county, 'county')->create(['assigned_to' => $icto->id]);

        AssetStatusLog::factory()->create([
            'asset_id' => $loggedAsset->id,
            'user_id' => $icto->id,
            'logged_date' => now()->toDateString(),
        ]);

        $this->actingAs($icto)
            ->get(route('status-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logged_count', 1)
                ->where('total_count', 2)
            );
    });

    it('guest is redirected to login', function () {
        $this->get(route('status-logs.index'))
            ->assertRedirect(route('login'));
    });
});

// ─── Create ───────────────────────────────────────────────────────────────────

describe('status log create', function () {
    it('icto can view the log form for an asset in their region', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();

        $this->actingAs(ictoForLog($region))
            ->get(route('assets.status-logs.create', $asset))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Assets/StatusLog/Create')
                ->where('asset.id', $asset->id)
                ->where('is_amendment', false)
            );
    });

    it('create form shows is_amendment true when a log already exists for today', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();
        $icto = ictoForLog($region);

        AssetStatusLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $icto->id,
            'logged_date' => now()->toDateString(),
        ]);

        $this->actingAs($icto)
            ->get(route('assets.status-logs.create', $asset))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('is_amendment', true));
    });

    it('icto cannot view the create form for an asset in a different region', function () {
        [$regionA, $regionB] = Region::factory()->count(2)->create();
        $countyB = County::factory()->for($regionB)->create();
        $assetInRegionB = Asset::factory()->for($countyB, 'county')->create();

        // Policy enforces region ownership (route model binding bypasses RegionScope)
        $this->actingAs(ictoForLog($regionA))
            ->get(route('assets.status-logs.create', $assetInRegionB))
            ->assertForbidden();
    });

    it('ricto cannot view the create form', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();
        $ricto = User::factory()->create(['region_id' => $region->id]);
        $ricto->assignRole('ricto');

        $this->actingAs($ricto)
            ->get(route('assets.status-logs.create', $asset))
            ->assertForbidden();
    });
});

// ─── Store ────────────────────────────────────────────────────────────────────

describe('status log store', function () {
    it('icto can log status for an asset in their region', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();
        $icto = ictoForLog($region);

        $this->actingAs($icto)
            ->post(route('assets.status-logs.store', $asset), [
                'status' => AssetLogStatus::Operational->value,
                'remarks' => 'All systems nominal.',
            ])
            ->assertRedirect(route('status-logs.index'));

        $log = AssetStatusLog::where('asset_id', $asset->id)
            ->where('user_id', $icto->id)
            ->first();

        expect($log)->not->toBeNull();
        expect($log->status)->toBe(AssetLogStatus::Operational);
        expect($log->is_amendment)->toBeFalse();
        expect($log->synced_at)->not->toBeNull();
    });

    it('aicto can log status for an asset in their region', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();
        $aicto = aictoForLog($region);

        $this->actingAs($aicto)
            ->post(route('assets.status-logs.store', $asset), [
                'status' => AssetLogStatus::Degraded->value,
            ])
            ->assertRedirect(route('status-logs.index'));

        expect(
            AssetStatusLog::where('asset_id', $asset->id)
                ->where('user_id', $aicto->id)
                ->exists()
        )->toBeTrue();
    });

    it('icto cannot log status for an asset in a different region', function () {
        [$regionA, $regionB] = Region::factory()->count(2)->create();
        $countyB = County::factory()->for($regionB)->create();
        $assetInRegionB = Asset::factory()->for($countyB, 'county')->create();

        // Policy enforces region ownership (route model binding bypasses RegionScope)
        $this->actingAs(ictoForLog($regionA))
            ->post(route('assets.status-logs.store', $assetInRegionB), [
                'status' => AssetLogStatus::Operational->value,
            ])
            ->assertForbidden();

        expect(AssetStatusLog::where('asset_id', $assetInRegionB->id)->exists())->toBeFalse();
    });

    it('second log for same asset same day creates an amendment row', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();
        $icto = ictoForLog($region);

        // Seed the first log directly
        AssetStatusLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $icto->id,
            'logged_date' => now()->toDateString(),
            'is_amendment' => false,
        ]);

        $this->actingAs($icto)
            ->post(route('assets.status-logs.store', $asset), [
                'status' => AssetLogStatus::Degraded->value,
                'amendment_reason' => 'Correcting earlier entry — link quality dropped.',
            ])
            ->assertRedirect(route('status-logs.index'));

        $amendment = AssetStatusLog::where('asset_id', $asset->id)
            ->where('user_id', $icto->id)
            ->where('is_amendment', true)
            ->first();

        expect($amendment)->not->toBeNull();
        expect($amendment->amendment_reason)->toBe('Correcting earlier entry — link quality dropped.');
        expect($amendment->status)->toBe(AssetLogStatus::Degraded);
    });

    it('amendment without amendment_reason is rejected with validation error', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();
        $icto = ictoForLog($region);

        AssetStatusLog::factory()->create([
            'asset_id' => $asset->id,
            'user_id' => $icto->id,
            'logged_date' => now()->toDateString(),
        ]);

        $this->actingAs($icto)
            ->post(route('assets.status-logs.store', $asset), [
                'status' => AssetLogStatus::Down->value,
                // amendment_reason deliberately omitted
            ])
            ->assertSessionHasErrors('amendment_reason');

        expect(
            AssetStatusLog::where('asset_id', $asset->id)
                ->where('is_amendment', true)
                ->exists()
        )->toBeFalse();
    });

    it('admin can log status for any asset', function () {
        $admin = adminForLog();
        $asset = Asset::factory()->create();

        $this->actingAs($admin)
            ->post(route('assets.status-logs.store', $asset), [
                'status' => AssetLogStatus::Maintenance->value,
                'remarks' => 'Scheduled maintenance window.',
            ])
            ->assertRedirect(route('status-logs.index'));

        expect(
            AssetStatusLog::where('asset_id', $asset->id)
                ->where('user_id', $admin->id)
                ->exists()
        )->toBeTrue();
    });

    it('store fails validation when status is missing', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();

        $this->actingAs(ictoForLog($region))
            ->post(route('assets.status-logs.store', $asset), [])
            ->assertSessionHasErrors('status');
    });

    it('store fails validation when latitude is out of range', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();

        $this->actingAs(ictoForLog($region))
            ->post(route('assets.status-logs.store', $asset), [
                'status' => AssetLogStatus::Operational->value,
                'latitude' => 95.0,
            ])
            ->assertSessionHasErrors('latitude');
    });

    it('store fails validation when status value is invalid', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();

        $this->actingAs(ictoForLog($region))
            ->post(route('assets.status-logs.store', $asset), [
                'status' => 'decommissioned', // not a valid AssetLogStatus
            ])
            ->assertSessionHasErrors('status');
    });

    it('ricto gets 403 when trying to log status', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();
        $ricto = User::factory()->create(['region_id' => $region->id]);
        $ricto->assignRole('ricto');

        $this->actingAs($ricto)
            ->post(route('assets.status-logs.store', $asset), [
                'status' => AssetLogStatus::Operational->value,
            ])
            ->assertForbidden();
    });

    it('synced_at is set to now when submitted online', function () {
        $region = Region::factory()->create();
        $county = County::factory()->for($region)->create();
        $asset = Asset::factory()->for($county, 'county')->create();

        $this->actingAs(ictoForLog($region))
            ->post(route('assets.status-logs.store', $asset), [
                'status' => AssetLogStatus::Operational->value,
            ]);

        $log = AssetStatusLog::where('asset_id', $asset->id)->first();
        expect($log->synced_at)->not->toBeNull();
    });
});
