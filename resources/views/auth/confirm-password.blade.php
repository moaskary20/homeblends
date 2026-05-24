@extends('layouts.auth', ['title' => __('Confirm Password')])

@section('auth_content')
    <h1 class="hb-auth-title">{{ __('Confirm Password') }}</h1>
    <p class="hb-auth-subtitle">{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</p>

    @if ($errors->any())
        <div class="hb-auth-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="hb-auth-field">
            <label for="password">{{ __('ecommerce.password') }}</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="current-password">
        </div>
        <button type="submit" class="hb-auth-btn">{{ __('Confirm') }}</button>
    </form>
@endsection
