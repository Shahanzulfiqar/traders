@extends('layouts.auth')

@section('title', 'Dashboard')

@section('content')

    <div class="modal-content cs_modal">
        <div class="modal-header justify-content-center theme_bg_1">
            <h5 class="modal-title text_white">Log in</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="mb-3">
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        placeholder="Enter your email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        placeholder="Password" required>
                    @error('password')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember Me Checkbox -->
                <div class="cs_check_box mb-3">
                    <input type="checkbox" id="remember" name="remember" class="common_checkbox"
                        {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-label" for="remember">
                        Remember Me
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn_1 full_width text-center">Log in</button>

                <!-- Links -->
                <p class="mt-3 text-center">
                    Need an account?
                    <a href="{{ route('register') }}">Sign Up</a>
                </p>

                <div class="text-center">
                    <a href="{{ route('password.request') }}" class="pass_forget_btn">
                        Forgot Password?
                    </a>
                </div>
            </form>
        </div>
    </div>

@endsection
