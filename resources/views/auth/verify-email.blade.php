@extends('layouts.auth', ['title' => __('Verify Email')])

@section('auth_content')
    <h1 class="hb-auth-title">{{ __('Verify Email') }}</h1>
    <p class="hb-auth-subtitle">{{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}</p>

    @if (session('status') === 'verification-link-sent')
        <div class="p-3 mb-4 bg-green-50 text-green-800 rounded-lg text-sm">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="hb-auth-btn">{{ __('Resend Verification Email') }}</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="w-full text-sm text-gray-600 hover:text-amber-700">{{ __('Log Out') }}</button>
    </form>
@endsection
