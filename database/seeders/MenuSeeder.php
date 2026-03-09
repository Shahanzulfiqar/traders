<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Menu::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Dashboard
        Menu::create([
            'name' => 'Dashboard',
            'route' => 'dashboard',
            'icon' => 'img/menu-icon/1.svg',
            'parent_id' => null,
            'order' => 1,
            'status' => 1
        ]);

        // Inventory Parent
        $inventory = Menu::create([
            'name' => 'Inventory',
            'route' => null,
            'icon' => 'img/menu-icon/6.svg',
            'parent_id' => null,
            'order' => 2,
            'status' => 1
        ]);

        Menu::create([
            'name' => 'Manufacturers',
            'route' => 'manufacturers.index',
            'parent_id' => $inventory->id,
            'order' => 1,
            'status' => 1
        ]);

        Menu::create([
            'name' => 'Brands',
            'route' => 'brands.index',
            'parent_id' => $inventory->id,
            'order' => 2,
            'status' => 1
        ]);

        Menu::create([
            'name' => 'Products',
            'route' => 'products.index',
            'parent_id' => $inventory->id,
            'order' => 3,
            'status' => 1
        ]);

        // Roles
        $roles = Menu::create([
            'name' => 'Roles & Permissions',
            'icon' => 'img/menu-icon/General.svg',
            'parent_id' => null,
            'order' => 3,
            'status' => 1
        ]);

        Menu::create([
            'name' => 'Roles',
            'route' => 'roles.index',
            'parent_id' => $roles->id,
            'order' => 1,
            'status' => 1
        ]);

        Menu::create([
            'name' => 'Role Permissions',
            'route' => 'rolepermissions.index',
            'parent_id' => $roles->id,
            'order' => 2,
            'status' => 1
        ]);

        // Users
        $users = Menu::create([
            'name' => 'Users',
            'icon' => 'img/menu-icon/General.svg',
            'parent_id' => null,
            'order' => 4,
            'status' => 1
        ]);

        Menu::create([
            'name' => 'Users List',
            'route' => 'users.index',
            'parent_id' => $users->id,
            'order' => 1,
            'status' => 1
        ]);

        Menu::create([
            'name' => 'Create User',
            'route' => 'users.create',
            'parent_id' => $users->id,
            'order' => 2,
            'status' => 1
        ]);

        // Menu Manager
        Menu::create([
            'name' => 'Menus',
            'route' => 'menus.index',
            'icon' => 'img/menu-icon/General.svg',
            'parent_id' => null,
            'order' => 5,
            'status' => 1
        ]);
    }
}