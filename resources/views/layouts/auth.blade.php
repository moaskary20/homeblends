<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('ecommerce.login') }} — {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    @include('partials.vite-assets')
    <link rel="stylesheet" href="{{ asset('css/shop-header.css') }}">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .hb-auth-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f5f0e6; padding: 2rem 1rem; }
        .hb-auth-card { width: 100%; max-width: 420px; background: #fff; border-radius: 1rem; box-shadow: 0 8px 32px rgba(0,0,0,0.08); padding: 2rem; }
        .hb-auth-title { font-size: 1.5rem; font-weight: 800; color: #3d3830; margin-bottom: 0.5rem; text-align: center; }
        .hb-auth-subtitle { text-align: center; color: #6b7280; font-size: 0.875rem; margin-bottom: 1.5rem; }
        .hb-auth-field { margin-bottom: 1rem; }
        .hb-auth-field label { display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.35rem; color: #374151; }
        .hb-auth-field input { width: 100%; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.65rem 0.85rem; font-size: 0.95rem; }
        .hb-auth-field input:focus { outline: none; border-color: #b45309; box-shadow: 0 0 0 3px rgba(180,83,9,0.15); }
        .hb-auth-btn { width: 100%; background: #b45309; color: #fff; font-weight: 700; padding: 0.75rem; border-radius: 0.5rem; border: none; cursor: pointer; margin-top: 0.5rem; }
        .hb-auth-btn:hover { background: #92400e; }
        .hb-auth-links { margin-top: 1.25rem; text-align: center; font-size: 0.875rem; color: #6b7280; }
        .hb-auth-links a { color: #b45309; font-weight: 600; }
        .hb-auth-error { background: #fef2f2; color: #b91c1c; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; margin-bottom: 1rem; }
        .hb-auth-logo { display: block; text-align: center; font-size: 1.25rem; font-weight: 800; color: #92400e; margin-bottom: 1.5rem; text-decoration: none; }
    </style>
</head>
<body>
    <div class="hb-auth-page">
        <div class="hb-auth-card">
            <a href="{{ route('shop.home') }}" class="hb-auth-logo">{{ config('app.name') }}</a>
            @yield('auth_content')
        </div>
    </div>
</body>
</html>
