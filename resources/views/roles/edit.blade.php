@extends('layouts.app')

@section('content')
    <div class="container">

        <h3>Edit Role</h3>

        <form action="{{ route('roles.update', $role->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Role Name</label>
                <input type="text" name="name" value="{{ $role->name }}" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">
                Update
            </button>

            <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                Back
            </a>
        </form>

    </div>
@endsection
