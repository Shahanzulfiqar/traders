@extends('layouts.app')

@section('content')
    <div class="container">

        <h3>Create User</h3>

        <form action="{{ route('users.store') }}" method="POST">

            @csrf

            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control">
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control">
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control">
            </div>

            <div class="mb-3">
                <label>Role</label>

                <select name="role" class="form-control">

                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}">
                            {{ $role->name }}
                        </option>
                    @endforeach

                </select>

            </div>

            <button class="btn btn-success">
                Save
            </button>

        </form>

    </div>
@endsection
