@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')

    <div class="modal-content cs_modal">
        <div class="modal-header theme_bg_1 justify-content-center">
            <h5 class="text_white">Reset Password</h5>
        </div>

        <div class="modal-body">

            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <input type="email" name="email" class="form-control" value="{{ request()->email }}"
                        placeholder="Email" required>
                </div>

                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="New Password" required>
                </div>

                <div class="mb-3">
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password"
                        required>
                </div>

                <button type="submit" class="btn_1 full_width">
                    Reset Password
                </button>

            </form>
        </div>
    </div>

@endsection
