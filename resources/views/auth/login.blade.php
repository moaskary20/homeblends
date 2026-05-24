@extends('layouts.auth', ['title' => __('ecommerce.login')])

@section('auth_content')
    <h1 class="hb-auth-title">{{ __('ecommerce.login') }}</h1>
    <p class="hb-auth-subtitle">{{ __('ecommerce.login_welcome') }}</p>

    @if ($errors->any())
        <div class="hb-auth-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if (session('status'))
        <div class="p-3 mb-4 bg-green-50 text-green-800 rounded-lg text-sm">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="hb-auth-field">
            <label for="email">{{ __('ecommerce.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>
        <div class="hb-auth-field">
            <label for="password">{{ __('ecommerce.password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>
        <div class="flex items-center gap-2 mb-4">
            <input id="remember" type="checkbox" name="remember" class="rounded border-gray-300">
            <label for="remember" class="text-sm text-gray-600">{{ __('Remember me') }}</label>
        </div>
        <button type="submit" class="hb-auth-btn">{{ __('ecommerce.login') }}</button>
    </form>

    <div class="hb-auth-links">
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
        @endif
        @if (Route::has('register'))
            <p class="mt-2">{{ __('ecommerce.register_prompt') }} <a href="{{ route('register') }}">{{ __('ecommerce.register') }}</a></p>
        @endif
    </div>
@endsection
