@extends('layouts.app')

@section('content')
    <div class="container">

        <h3>Create Role</h3>

        <form action="{{ route('roles.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label>Role Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-success">
                Save
            </button>

            <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                Back
            </a>
        </form>

    </div>
@endsection
