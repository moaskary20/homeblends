@extends('layouts.auth', ['title' => __('Forgot Password')])

@section('auth_content')
    <h1 class="hb-auth-title">{{ __('Forgot Password') }}</h1>
    <p class="hb-auth-subtitle">{{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.') }}</p>

    @if (session('status'))
        <div class="p-3 mb-4 bg-green-50 text-green-800 rounded-lg text-sm">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="hb-auth-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="hb-auth-field">
            <label for="email">{{ __('ecommerce.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <button type="submit" class="hb-auth-btn">{{ __('Email Password Reset Link') }}</button>
    </form>

    <div class="hb-auth-links">
        <a href="{{ route('login') }}">{{ __('ecommerce.login') }}</a>
    </div>
@endsection
