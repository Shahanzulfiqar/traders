<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\RoleMenuPermission;

class MenuService
{
    public static function getUserMenus($user)
    {
        $roleIds = $user->roles->pluck('id');

        $allowedMenuIds = RoleMenuPermission::whereIn('role_id', $roleIds)
            ->where('can_view', 1)
            ->pluck('menu_id');

        return Menu::whereIn('id', $allowedMenuIds)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) use ($allowedMenuIds) {
                $q->whereIn('id', $allowedMenuIds);
            }])
            ->orderBy('order')
            ->get();
    }
}