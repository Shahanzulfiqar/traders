<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use Spatie\Permission\Models\Role;
use App\Models\RoleMenuPermission;


class RolePermissionController extends Controller
{


    public function index()
    {
        $roles = Role::all();
        $menus = Menu::all();
        $menus = Menu::with('children')->whereNull('parent_id')->get();

        return view('rolepermissions.index', compact('roles','menus'));
    }
    public function store(Request $request)
{
    foreach ($request->permissions as $menu_id => $permission) {

        RoleMenuPermission::updateOrCreate(
        [
            'role_id' => $request->role_id,
            'menu_id' => $menu_id
        ],
        [
            'can_view' => isset($permission['view']),
            'can_add' => isset($permission['add']),
            'can_edit' => isset($permission['edit']),
            'can_delete' => isset($permission['delete'])
        ]
        );

    }

    return back()->with('success','Permissions saved');
}
}








