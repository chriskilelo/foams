<?php

use App\Models\County;
use App\Models\Region;
use App\Models\SlaConfiguration;
use App\Models\User;
use Database\Seeders\CountySeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SlaConfigurationSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\Models\Role;

// ─── RoleSeeder ───────────────────────────────────────────────────────────────

describe('RoleSeeder', function () {
    beforeEach(fn () => $this->seed(RoleSeeder::class));

    it('creates all 8 FOAMS roles', function () {
        expect(Role::count())->toBe(8);
    });

    it('creates every expected role slug', function (string $slug) {
        expect(Role::where('name', $slug)->exists())->toBeTrue();
    })->with(['admin', 'director', 'noc', 'ricto', 'icto', 'aicto', 'public_servant', 'public']);

    it('is idempotent when run twice', function () {
        $this->seed(RoleSeeder::class);

        expect(Role::count())->toBe(8);
    });
});

// ─── RegionSeeder ─────────────────────────────────────────────────────────────

describe('RegionSeeder', function () {
    beforeEach(fn () => $this->seed(RegionSeeder::class));

    it('creates all 8 regions', function () {
        expect(Region::count())->toBe(8);
    });

    it('creates every region with correct code', function (string $name, string $code) {
        expect(Region::where('name', $name)->value('code'))->toBe($code);
    })->with([
        ['Nairobi',       'NBI'],
        ['Coast',         'CST'],
        ['North Eastern', 'NEA'],
        ['Eastern',       'EAS'],
        ['Central',       'CTR'],
        ['Rift Valley',   'RFT'],
        ['Nyanza',        'NYZ'],
        ['Western',       'WST'],
    ]);

    it('marks all regions as active', function () {
        expect(Region::where('is_active', false)->count())->toBe(0);
    });

    it('is idempotent when run twice', function () {
        $this->seed(RegionSeeder::class);

        expect(Region::count())->toBe(8);
    });
});

// ─── CountySeeder ─────────────────────────────────────────────────────────────

describe('CountySeeder', function () {
    beforeEach(function () {
        $this->seed(RegionSeeder::class);
        $this->seed(CountySeeder::class);
    });

    it('creates all 47 counties', function () {
        expect(County::count())->toBe(47);
    });

    it('assigns every county to a region', function () {
        expect(County::whereNull('region_id')->count())->toBe(0);
    });

    it('assigns counties to correct region counts', function (string $regionName, int $expectedCount) {
        $region = Region::where('name', $regionName)->firstOrFail();

        expect(County::where('region_id', $region->id)->count())->toBe($expectedCount);
    })->with([
        ['Nairobi',        1],
        ['Central',        5],
        ['Coast',          6],
        ['North Eastern',  3],
        ['Eastern',        8],
        ['Rift Valley',   14],
        ['Western',        4],
        ['Nyanza',         6],
    ]);

    it('assigns Nairobi City to Nairobi region', function () {
        $nairobiRegion = Region::where('name', 'Nairobi')->firstOrFail();
        $county = County::where('name', 'Nairobi City')->firstOrFail();

        expect($county->region_id)->toBe($nairobiRegion->id);
    });

    it('assigns Mombasa to Coast region', function () {
        $coastRegion = Region::where('name', 'Coast')->firstOrFail();
        $county = County::where('name', 'Mombasa')->firstOrFail();

        expect($county->region_id)->toBe($coastRegion->id);
    });

    it('is idempotent when run twice', function () {
        $this->seed(CountySeeder::class);

        expect(County::count())->toBe(47);
    });
});

// ─── UserSeeder ───────────────────────────────────────────────────────────────

describe('UserSeeder', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        $this->seed(RegionSeeder::class);
        $this->seed(CountySeeder::class);
        $this->seed(UserSeeder::class);
    });

    it('creates one user per role (8 total)', function () {
        expect(User::count())->toBe(8);
    });

    it('assigns each user exactly one role', function (string $email, string $role) {
        $user = User::where('email', $email)->firstOrFail();

        expect($user->hasRole($role))->toBeTrue();
    })->with([
        ['admin@foams.ict.go.ke',          'admin'],
        ['director@foams.ict.go.ke',       'director'],
        ['noc@foams.ict.go.ke',            'noc'],
        ['ricto.nairobi@foams.ict.go.ke',  'ricto'],
        ['icto.coast@foams.ict.go.ke',     'icto'],
        ['aicto.rift@foams.ict.go.ke',     'aicto'],
        ['servant@foams.ict.go.ke',        'public_servant'],
        ['public@foams.ict.go.ke',         'public'],
    ]);

    it('assigns Nairobi region to RICTO user', function () {
        $user = User::where('email', 'ricto.nairobi@foams.ict.go.ke')->firstOrFail();
        $region = Region::where('name', 'Nairobi')->firstOrFail();

        expect($user->region_id)->toBe($region->id);
    });

    it('assigns Coast region to ICTO user', function () {
        $user = User::where('email', 'icto.coast@foams.ict.go.ke')->firstOrFail();
        $region = Region::where('name', 'Coast')->firstOrFail();

        expect($user->region_id)->toBe($region->id);
    });

    it('assigns Rift Valley region to AICTO user', function () {
        $user = User::where('email', 'aicto.rift@foams.ict.go.ke')->firstOrFail();
        $region = Region::where('name', 'Rift Valley')->firstOrFail();

        expect($user->region_id)->toBe($region->id);
    });

    it('leaves admin, director, noc, public_servant, public without a region', function (string $email) {
        $user = User::where('email', $email)->firstOrFail();

        expect($user->region_id)->toBeNull();
    })->with([
        'admin@foams.ict.go.ke',
        'director@foams.ict.go.ke',
        'noc@foams.ict.go.ke',
        'servant@foams.ict.go.ke',
        'public@foams.ict.go.ke',
    ]);

    it('marks all seeded users as active', function () {
        expect(User::where('is_active', false)->count())->toBe(0);
    });

    it('is idempotent when run twice', function () {
        $this->seed(UserSeeder::class);

        expect(User::count())->toBe(8);
    });
});

// ─── SlaConfigurationSeeder ───────────────────────────────────────────────────

describe('SlaConfigurationSeeder', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        $this->seed(RegionSeeder::class);
        $this->seed(CountySeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(SlaConfigurationSeeder::class);
    });

    it('creates 4 SLA configurations', function () {
        expect(SlaConfiguration::count())->toBe(4);
    });

    it('sets correct SLA hours per severity', function (string $severity, int $ackHrs, int $resolveHrs) {
        $sla = SlaConfiguration::where('severity', $severity)->firstOrFail();

        expect($sla->acknowledge_within_hrs)->toBe($ackHrs)
            ->and($sla->resolve_within_hrs)->toBe($resolveHrs);
    })->with([
        ['critical', 1,  4],
        ['high',     4,  8],
        ['medium',   8,  24],
        ['low',      24, 72],
    ]);

    it('links created_by_user_id to the admin user', function () {
        $admin = User::role('admin')->firstOrFail();

        expect(SlaConfiguration::where('created_by_user_id', $admin->id)->count())->toBe(4);
    });

    it('is idempotent when run twice', function () {
        $this->seed(SlaConfigurationSeeder::class);

        expect(SlaConfiguration::count())->toBe(4);
    });
});
