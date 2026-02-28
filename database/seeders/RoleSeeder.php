<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * The 8 FOAMS roles in order of descending privilege.
     *
     * @var list<string>
     */
    private array $roles = [
        'admin',
        'director',
        'noc',
        'ricto',
        'icto',
        'aicto',
        'public_servant',
        'public',
    ];

    public function run(): void
    {
        foreach ($this->roles as $slug) {
            Role::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
        }
    }
}
