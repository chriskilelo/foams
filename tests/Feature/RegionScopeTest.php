<?php

use App\Http\Middleware\RegionScopeMiddleware;
use App\Models\Asset;
use App\Models\County;
use App\Models\Issue;
use App\Models\Region;
use App\Models\Scopes\RegionScope;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Run the RegionScopeMiddleware for a given user and return control.
 *
 * This exercises the real middleware handle() method so that any global scopes
 * it registers land on the model classes, exactly as they would during a real
 * HTTP request.
 */
function applyRegionScopeMiddleware(User $user): void
{
    $request = Request::create('/dashboard', 'GET');
    $request->setUserResolver(fn () => $user);

    (new RegionScopeMiddleware)->handle($request, fn () => new Response);
}

/**
 * Remove RegionScope from the static global-scope registry after each test so
 * scopes registered for one test cannot bleed into the next.
 */
function clearRegionScope(): void
{
    $ref = new ReflectionProperty(Model::class, 'globalScopes');
    $scopes = $ref->getValue(null);
    unset($scopes[Asset::class][RegionScope::class]);
    unset($scopes[Issue::class][RegionScope::class]);
    $ref->setValue(null, $scopes);
}

// ─── RegionScopeMiddleware ────────────────────────────────────────────────────

describe('RegionScopeMiddleware', function () {
    beforeEach(fn () => $this->seed(RoleSeeder::class));

    afterEach(fn () => clearRegionScope());

    it('RICTO cannot see assets belonging to a different region', function () {
        [$regionA, $regionB] = Region::factory()->count(2)->create();
        $countyA = County::factory()->for($regionA)->create();
        $countyB = County::factory()->for($regionB)->create();

        $assetA = Asset::factory()->for($countyA, 'county')->create();
        $assetB = Asset::factory()->for($countyB, 'county')->create();

        $ricto = User::factory()->create(['region_id' => $regionA->id]);
        $ricto->assignRole('ricto');

        applyRegionScopeMiddleware($ricto);

        $visible = Asset::all()->pluck('id');

        expect($visible)
            ->toContain($assetA->id)
            ->not->toContain($assetB->id);
    });

    it('ICTO cannot see issues belonging to a different region', function () {
        [$regionA, $regionB] = Region::factory()->count(2)->create();
        $countyA = County::factory()->for($regionA)->create();
        $countyB = County::factory()->for($regionB)->create();

        // Issues are filtered by their own county_id — asset is irrelevant here.
        // Pass asset_id => null to prevent the factory spawning extra counties.
        $issueA = Issue::factory()->for($countyA, 'county')->create(['asset_id' => null]);
        $issueB = Issue::factory()->for($countyB, 'county')->create(['asset_id' => null]);

        $icto = User::factory()->create(['region_id' => $regionA->id]);
        $icto->assignRole('icto');

        applyRegionScopeMiddleware($icto);

        $visible = Issue::all()->pluck('id');

        expect($visible)
            ->toContain($issueA->id)
            ->not->toContain($issueB->id);
    });

    it('admin can see assets across all regions', function () {
        [$regionA, $regionB] = Region::factory()->count(2)->create();
        $countyA = County::factory()->for($regionA)->create();
        $countyB = County::factory()->for($regionB)->create();

        Asset::factory()->for($countyA, 'county')->create();
        Asset::factory()->for($countyB, 'county')->create();

        $admin = User::factory()->create(['region_id' => null]);
        $admin->assignRole('admin');

        applyRegionScopeMiddleware($admin);

        // No RegionScope is registered for admin — all assets are visible.
        expect(Asset::count())->toBe(2);
    });

    it('NOC can see issues across all regions', function () {
        [$regionA, $regionB] = Region::factory()->count(2)->create();
        $countyA = County::factory()->for($regionA)->create();
        $countyB = County::factory()->for($regionB)->create();

        Issue::factory()->for($countyA, 'county')->create(['asset_id' => null]);
        Issue::factory()->for($countyB, 'county')->create(['asset_id' => null]);

        $noc = User::factory()->create(['region_id' => null]);
        $noc->assignRole('noc');

        applyRegionScopeMiddleware($noc);

        // No RegionScope is registered for NOC — all issues are visible.
        expect(Issue::count())->toBe(2);
    });
});
