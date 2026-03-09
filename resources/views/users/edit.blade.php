@extends('layouts.app')

@section('content')
    <div class="container">

        <h3>Edit User</h3>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('users.update', $user->id) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" value="{{ $user->name }}">
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ $user->email }}">
            </div>

            <div class="mb-3">
                <label>Role</label>

                <select name="role" class="form-control">

                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @if ($user->roles->first() && $user->roles->first()->name == $role->name) selected @endif>

                            {{ $role->name }}

                        </option>
                    @endforeach

                </select>

            </div>

            <button type="submit" class="btn btn-success">
                Update User
            </button>

            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                Back
            </a>

        </form>

    </div>
@endsection
