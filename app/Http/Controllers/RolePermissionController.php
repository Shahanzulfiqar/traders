<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Menu;
use Spatie\Permission\Models\Role;
use App\Models\RoleMenuPermission;

class RolePermissionController extends Controller
{
    /**
     * Show Role Permissions page
     */
    public function index(Request $request)
    {
        $roles = Role::all();
        $menus = Menu::with('children')->whereNull('parent_id')->get();

        // Default selected role (first role)
        $selectedRoleId = $request->role_id ?? ($roles->first()->id ?? null);

        // Load existing permissions for the selected role
        $rolePermissions = [];
        if ($selectedRoleId) {
            $permissions = RoleMenuPermission::where('role_id', $selectedRoleId)->get();
            foreach ($permissions as $perm) {
                $rolePermissions[$perm->menu_id] = [
                    'view' => $perm->can_view,
                    'add' => $perm->can_add,
                    'edit' => $perm->can_edit,
                    'delete' => $perm->can_delete,
                ];
            }
        }

        return view('rolepermissions.index', compact('roles', 'menus', 'rolePermissions', 'selectedRoleId'));
    }

    /**
     * Store Role Permissions
     */
    public function store(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'array'
        ]);

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

        return back()->with('success', 'Permissions saved successfully!');
    }

    /**
     * Optional: AJAX endpoint to fetch permissions dynamically
     */
    public function getPermissions(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id'
        ]);

        $permissions = RoleMenuPermission::where('role_id', $request->role_id)->get();
        $rolePermissions = [];

        foreach ($permissions as $perm) {
            $rolePermissions[$perm->menu_id] = [
                'view' => $perm->can_view,
                'add' => $perm->can_add,
                'edit' => $perm->can_edit,
                'delete' => $perm->can_delete,
            ];
        }

        return response()->json($rolePermissions);
    }

}
