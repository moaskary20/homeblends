@extends('layouts.auth', ['title' => __('Reset Password')])

@section('auth_content')
    <h1 class="hb-auth-title">{{ __('Reset Password') }}</h1>

    @if ($errors->any())
        <div class="hb-auth-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="hb-auth-field">
            <label for="email">{{ __('ecommerce.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus>
        </div>
        <div class="hb-auth-field">
            <label for="password">{{ __('ecommerce.password') }}</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
        </div>
        <div class="hb-auth-field">
            <label for="password_confirmation">{{ __('ecommerce.password_confirmation') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="hb-auth-btn">{{ __('Reset Password') }}</button>
    </form>
@endsection
