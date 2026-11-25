<?php

namespace Database\Seeders;

use App\Support\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Safe to run more than once.
     */
    public function run(): void
    {
        foreach (Roles::PANEL as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
