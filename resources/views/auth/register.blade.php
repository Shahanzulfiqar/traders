@extends('layouts.auth')

@section('title', 'Dashboard')

@section('content')

    <div class="modal-content cs_modal">
        <div class="modal-header theme_bg_1 justify-content-center">
            <h5 class="modal-title text_white">Register</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Full Name -->
                <div class="mb-3">
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        placeholder="Full Name" value="{{ old('name') }}" required autofocus>
                    @error('name')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        placeholder="Enter your email" value="{{ old('email') }}" required>
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

                <!-- Confirm Password -->
                <div class="mb-3">
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password"
                        required>
                </div>

                <!-- Checkbox -->
                <div class="cs_check_box mb-3">
                    <input type="checkbox" id="check_box" class="common_checkbox" name="subscribe"
                        {{ old('subscribe') ? 'checked' : '' }}>
                    <label class="form-label" for="check_box">
                        Keep me up to date
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn_1 full_width text-center">Sign Up</button>

                <!-- Links -->
                <p class="mt-3 text-center">
                    Already have an account?
                    <a data-bs-toggle="modal" data-bs-target="#sing_up" data-bs-dismiss="modal" href="#">Log in</a>
                </p>

                <div class="text-center">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#forgot_password" data-bs-dismiss="modal"
                        class="pass_forget_btn">Forget Password?</a>
                </div>
            </form>
        </div>
    </div>

@endsection
