<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        // TODO: change these before seeding a live server.
        $superAdminUser = User::create([
            'first_name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => '12345678',
            'active' => true,
        ]);
        $superAdminUser->assignRole('SUPER-ADMIN');
    }
}
