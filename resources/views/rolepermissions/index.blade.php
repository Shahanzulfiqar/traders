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

            <div class="mb-3">
                <label>Select Role</label>
                <select name="role_id" class="form-control">
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

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
                            <td><input type="checkbox" name="permissions[{{ $menu->id }}][view]"></td>
                            <td><input type="checkbox" name="permissions[{{ $menu->id }}][add]"></td>
                            <td><input type="checkbox" name="permissions[{{ $menu->id }}][edit]"></td>
                            <td><input type="checkbox" name="permissions[{{ $menu->id }}][delete]"></td>
                        </tr>

                        @if ($menu->children->count())
                            @foreach ($menu->children as $child)
                                <tr>
                                    <td class="ps-4">— {{ $child->name }}</td>
                                    <td><input type="checkbox" name="permissions[{{ $child->id }}][view]"></td>
                                    <td><input type="checkbox" name="permissions[{{ $child->id }}][add]"></td>
                                    <td><input type="checkbox" name="permissions[{{ $child->id }}][edit]"></td>
                                    <td><input type="checkbox" name="permissions[{{ $child->id }}][delete]"></td>
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
