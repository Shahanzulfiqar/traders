<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\Menu;
use App\Models\RoleMenuPermission;

class RoleMenuPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::where('name', 'Super Admin')->first();

        if (!$superAdmin) {
            return;
        }

        $menus = Menu::all();

        foreach ($menus as $menu) {

            RoleMenuPermission::updateOrCreate(
                [
                    'role_id' => $superAdmin->id,
                    'menu_id' => $menu->id
                ],
                [
                    'can_view' => 1,
                    'can_add' => 1,
                    'can_edit' => 1,
                    'can_delete' => 1
                ]
            );

        }
    }
}