@extends('layouts.app')

@section('content')
    <div class="container">

        <h3>Users</h3>

        <a href="{{ route('users.create') }}" class="btn btn-primary mb-3">
            Add User
        </a>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <table class="table table-bordered">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                @foreach ($users as $user)
                    <tr>

                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->roles->first()->name ?? '' }}</td>

                        <td>

                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-info btn-sm">
                                Edit
                            </a>

                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline">

                                @csrf
                                @method('DELETE')

                                <button class="btn btn-danger btn-sm">
                                    Delete
                                </button>

                            </form>

                        </td>

                    </tr>
                @endforeach

            </tbody>

        </table>

    </div>
@endsection
