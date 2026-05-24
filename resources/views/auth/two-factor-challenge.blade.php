@extends('layouts.auth', ['title' => __('Two Factor Authentication')])

@section('auth_content')
    <h1 class="hb-auth-title">{{ __('Two Factor Authentication') }}</h1>
    <p class="hb-auth-subtitle">{{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}</p>

    @if ($errors->any())
        <div class="hb-auth-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf
        <div class="hb-auth-field">
            <label for="code">{{ __('Code') }}</label>
            <input id="code" type="text" name="code" inputmode="numeric" autofocus autocomplete="one-time-code">
        </div>
        <button type="submit" class="hb-auth-btn">{{ __('Login') }}</button>
    </form>

    <form method="POST" action="{{ route('two-factor.login') }}" class="mt-6 pt-6 border-t border-gray-100">
        @csrf
        <p class="text-sm text-gray-600 mb-3">{{ __('Or enter a recovery code:') }}</p>
        <div class="hb-auth-field">
            <label for="recovery_code">{{ __('Recovery Code') }}</label>
            <input id="recovery_code" type="text" name="recovery_code" autocomplete="one-time-code">
        </div>
        <button type="submit" class="hb-auth-btn">{{ __('Login') }}</button>
    </form>
@endsection
