@extends('layouts.auth', ['title' => __('ecommerce.register')])

@section('auth_content')
    <h1 class="hb-auth-title">{{ __('ecommerce.register') }}</h1>
    <p class="hb-auth-subtitle">{{ __('ecommerce.register_subtitle') }}</p>

    @if ($errors->any())
        <div class="hb-auth-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="hb-auth-field">
            <label for="name">{{ __('ecommerce.name') }}</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </div>
        <div class="hb-auth-field">
            <label for="email">{{ __('ecommerce.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        </div>
        <div class="hb-auth-field">
            <label for="password">{{ __('ecommerce.password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
        </div>
        <div class="hb-auth-field">
            <label for="password_confirmation">{{ __('ecommerce.password_confirmation') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="hb-auth-btn">{{ __('ecommerce.register') }}</button>
    </form>

    <div class="hb-auth-links">
        <a href="{{ route('login') }}">{{ __('ecommerce.already_have_account') }}</a>
    </div>
@endsection
