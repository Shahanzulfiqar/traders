<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        // Run base seeders first
        $this->call([
            RoleSeeder::class,
            MenuSeeder::class,
            RoleMenuPermissionSeeder::class,
        ]);

        // Create Admin User
        $user = User::firstOrCreate(
            ['email' => 'admin@trader.com'],
            [
                'name' => 'Umair Raza',
                'password' => bcrypt('123456789')
            ]
        );

        // Get Super Admin Role
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        if ($superAdminRole) {
            $user->assignRole($superAdminRole);
        }

    }
}