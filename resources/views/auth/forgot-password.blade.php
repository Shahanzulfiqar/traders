@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')

    <div class="modal-content cs_modal">
        <div class="modal-header theme_bg_1 justify-content-center">
            <h5 class="text_white">Forgot Password</h5>
        </div>

        <div class="modal-body">

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-3">
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>

                <button type="submit" class="btn_1 full_width">
                    Send Reset Link
                </button>

                <p class="text-center mt-3">
                    <a href="{{ route('login') }}">Back to Login</a>
                </p>

            </form>
        </div>
    </div>

@endsection
