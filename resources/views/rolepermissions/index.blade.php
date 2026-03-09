@extends('layouts.app')

@section('content')
    <div class="container">

        <h3>Role Permissions</h3>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="alert alert-success mt-2">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('role.permissions.store') }}" method="POST">
            @csrf

            {{-- Role Selection --}}
            <div class="mb-3">
                <label>Select Role</label>
                <select name="role_id" class="form-control">
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" {{ ($selectedRoleId ?? null) == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Permissions Table --}}
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Menu</th>
                        <th>View</th>
                        <th>Add</th>
                        <th>Edit</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($menus as $menu)
                        <tr>
                            <td><strong>{{ $menu->name }}</strong></td>
                            <td>
                                <input type="checkbox" name="permissions[{{ $menu->id }}][view]"
                                    {{ isset($rolePermissions[$menu->id]['view']) && $rolePermissions[$menu->id]['view'] ? 'checked' : '' }}>
                            </td>
                            <td>
                                <input type="checkbox" name="permissions[{{ $menu->id }}][add]"
                                    {{ isset($rolePermissions[$menu->id]['add']) && $rolePermissions[$menu->id]['add'] ? 'checked' : '' }}>
                            </td>
                            <td>
                                <input type="checkbox" name="permissions[{{ $menu->id }}][edit]"
                                    {{ isset($rolePermissions[$menu->id]['edit']) && $rolePermissions[$menu->id]['edit'] ? 'checked' : '' }}>
                            </td>
                            <td>
                                <input type="checkbox" name="permissions[{{ $menu->id }}][delete]"
                                    {{ isset($rolePermissions[$menu->id]['delete']) && $rolePermissions[$menu->id]['delete'] ? 'checked' : '' }}>
                            </td>
                        </tr>

                        {{-- Child Menus --}}
                        @if ($menu->children->count())
                            @foreach ($menu->children as $child)
                                <tr>
                                    <td class="ps-4">— {{ $child->name }}</td>
                                    <td>
                                        <input type="checkbox" name="permissions[{{ $child->id }}][view]"
                                            {{ isset($rolePermissions[$child->id]['view']) && $rolePermissions[$child->id]['view'] ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="permissions[{{ $child->id }}][add]"
                                            {{ isset($rolePermissions[$child->id]['add']) && $rolePermissions[$child->id]['add'] ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="permissions[{{ $child->id }}][edit]"
                                            {{ isset($rolePermissions[$child->id]['edit']) && $rolePermissions[$child->id]['edit'] ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="permissions[{{ $child->id }}][delete]"
                                            {{ isset($rolePermissions[$child->id]['delete']) && $rolePermissions[$child->id]['delete'] ? 'checked' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>

            <button class="btn btn-success">Save Permissions</button>
        </form>

    </div>
@endsection
