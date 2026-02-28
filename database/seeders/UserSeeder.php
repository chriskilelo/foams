<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * One representative dev/test user per FOAMS role.
     * Passwords are intentionally weak — dev environment only.
     *
     * @var list<array{name: string, username: string, email: string, phone: string, role: string, region: string|null}>
     */
    private array $users = [
        [
            'name' => 'System Administrator',
            'username' => 'admin',
            'email' => 'admin@foams.ict.go.ke',
            'phone' => '+254700000001',
            'role' => 'admin',
            'region' => null,
        ],
        [
            'name' => 'Director ICT',
            'username' => 'director',
            'email' => 'director@foams.ict.go.ke',
            'phone' => '+254700000002',
            'role' => 'director',
            'region' => null,
        ],
        [
            'name' => 'NOC Officer',
            'username' => 'noc_officer',
            'email' => 'noc@foams.ict.go.ke',
            'phone' => '+254700000003',
            'role' => 'noc',
            'region' => null,
        ],
        [
            'name' => 'RICTO Nairobi',
            'username' => 'ricto_nairobi',
            'email' => 'ricto.nairobi@foams.ict.go.ke',
            'phone' => '+254700000004',
            'role' => 'ricto',
            'region' => 'Nairobi',
        ],
        [
            'name' => 'ICTO Coast',
            'username' => 'icto_coast',
            'email' => 'icto.coast@foams.ict.go.ke',
            'phone' => '+254700000005',
            'role' => 'icto',
            'region' => 'Coast',
        ],
        [
            'name' => 'AICTO Rift Valley',
            'username' => 'aicto_rift',
            'email' => 'aicto.rift@foams.ict.go.ke',
            'phone' => '+254700000006',
            'role' => 'aicto',
            'region' => 'Rift Valley',
        ],
        [
            'name' => 'Public Servant Demo',
            'username' => 'pub_servant',
            'email' => 'servant@foams.ict.go.ke',
            'phone' => '+254700000007',
            'role' => 'public_servant',
            'region' => null,
        ],
        [
            'name' => 'General Public Demo',
            'username' => 'gen_public',
            'email' => 'public@foams.ict.go.ke',
            'phone' => '+254700000008',
            'role' => 'public',
            'region' => null,
        ],
    ];

    public function run(): void
    {
        $password = Hash::make('Password1234!');

        foreach ($this->users as $userData) {
            $regionId = null;

            if ($userData['region'] !== null) {
                $regionId = Region::where('name', $userData['region'])->value('id');
            }

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'phone' => $userData['phone'],
                    'password' => $password,
                    'region_id' => $regionId,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
