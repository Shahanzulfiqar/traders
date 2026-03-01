@extends('layouts.app')

@section('title', 'Profile')

@section('content')

    <div class="container">

        <h4>My Profile</h4>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" value="{{ auth()->user()->name }}" class="form-control">
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" value="{{ auth()->user()->email }}" class="form-control">
            </div>

            <button class="btn btn-primary">
                Update Profile
            </button>

        </form>

    </div>

@endsection
