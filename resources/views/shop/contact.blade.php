@extends('layouts.shop')

@section('body_class', 'contact-page')
@section('main_class', 'flex-1 w-full p-0 max-w-none')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/shop-contact.css') }}?v={{ filemtime(public_path('css/shop-contact.css')) }}">
    <script src="{{ asset('js/shop-contact.js') }}?v={{ filemtime(public_path('js/shop-contact.js')) }}" defer></script>
@endpush

@section('content')
    @php
        $page = $contactPage ?? [];
    @endphp

    <section class="hb-contact-hero" aria-labelledby="contact-page-title">
        @if(!empty($page['hero']['image_url']))
            <div class="hb-contact-hero__bg" data-parallax="0.18">
                <img src="{{ $page['hero']['image_url'] }}" alt="" loading="eager" decoding="async">
            </div>
        @endif
        <div class="hb-contact-hero__overlay"></div>
        <div class="hb-contact-hero__grid">
            <div class="hb-contact-hero__content hb-contact-reveal">
                @if(filled($page['hero']['eyebrow'] ?? null))
                    <p class="hb-contact-eyebrow">{{ $page['hero']['eyebrow'] }}</p>
                @endif
                <h1 id="contact-page-title" class="hb-contact-hero__title">{{ $page['hero']['title'] ?? $page['page_title'] }}</h1>
                @if(filled($page['hero']['subtitle'] ?? null))
                    <p class="hb-contact-hero__subtitle">{{ $page['hero']['subtitle'] }}</p>
                @endif
                <div class="hb-contact-hero__chips">
                    @if(filled($page['info']['phone'] ?? null))
                        <a href="{{ $page['info']['phone_link'] ?: 'tel:'.$page['info']['phone'] }}" class="hb-contact-chip" dir="ltr">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2.2a1 1 0 01.95.68l1.2 3.6a1 1 0 01-.25 1.02l-1.46 1.46a16 16 0 006.36 6.36l1.46-1.46a1 1 0 011.02-.25l3.6 1.2a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C9.82 21 3 14.18 3 6V5z"/></svg>
                            {{ $page['info']['phone'] }}
                        </a>
                    @endif
                    @if(filled($page['info']['email'] ?? null))
                        <a href="mailto:{{ $page['info']['email'] }}" class="hb-contact-chip">{{ $page['info']['email'] }}</a>
                    @endif
                </div>
            </div>
            @if(!empty($page['hero']['accent_image_url']))
                <div class="hb-contact-hero__visual hb-contact-reveal" data-delay="120">
                    <div class="hb-contact-hero__frame">
                        <img src="{{ $page['hero']['accent_image_url'] }}" alt="{{ __('ecommerce.contact_ceramic_visual') }}" loading="eager" decoding="async">
                        <span class="hb-contact-hero__badge">{{ __('ecommerce.contact_premium_finishes') }}</span>
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if(!empty($page['gallery']))
        <section class="hb-contact-gallery" aria-label="{{ __('ecommerce.contact_gallery') }}">
            <div class="hb-contact-gallery__track">
                @foreach(array_merge($page['gallery'], $page['gallery']) as $item)
                    <figure class="hb-contact-gallery__item hb-contact-reveal">
                        <img src="{{ $item['image_url'] }}" alt="{{ $item['label'] ?? '' }}" loading="lazy" decoding="async">
                        <figcaption>{{ $item['label'] ?? '' }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    <section class="hb-contact-main">
        <div class="hb-contact-main__inner">
            <div class="hb-contact-info">
                <div class="hb-contact-info__grid">
                    @if(filled($page['info']['address'] ?? null))
                        <article class="hb-contact-card hb-contact-reveal" data-tilt>
                            <span class="hb-contact-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-4.35 7-10a7 7 0 10-14 0c0 5.65 7 10 7 10z"/><circle cx="12" cy="11" r="2.5"/></svg>
                            </span>
                            <h2 class="hb-contact-card__label">{{ $page['info']['address_label'] ?? __('ecommerce.contact_address') }}</h2>
                            <p class="hb-contact-card__value">{{ $page['info']['address'] }}</p>
                        </article>
                    @endif

                    @if(filled($page['info']['phone'] ?? null))
                        <article class="hb-contact-card hb-contact-reveal" data-delay="80" data-tilt>
                            <span class="hb-contact-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2.2a1 1 0 01.95.68l1.2 3.6a1 1 0 01-.25 1.02l-1.46 1.46a16 16 0 006.36 6.36l1.46-1.46a1 1 0 011.02-.25l3.6 1.2a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C9.82 21 3 14.18 3 6V5z"/></svg>
                            </span>
                            <h2 class="hb-contact-card__label">{{ $page['info']['phone_label'] ?? __('ecommerce.contact_phone') }}</h2>
                            <a href="{{ $page['info']['phone_link'] ?: 'tel:'.$page['info']['phone'] }}" class="hb-contact-card__link" dir="ltr">{{ $page['info']['phone'] }}</a>
                        </article>
                    @endif

                    @if(filled($page['info']['email'] ?? null))
                        <article class="hb-contact-card hb-contact-reveal" data-delay="160" data-tilt>
                            <span class="hb-contact-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v12H4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 7l8 6 8-6"/></svg>
                            </span>
                            <h2 class="hb-contact-card__label">{{ $page['info']['email_label'] ?? __('ecommerce.contact_email') }}</h2>
                            <a href="mailto:{{ $page['info']['email'] }}" class="hb-contact-card__link">{{ $page['info']['email'] }}</a>
                        </article>
                    @endif
                </div>

                @if(!empty($page['social']))
                    <div class="hb-contact-social hb-contact-reveal" data-delay="200">
                        @if(filled($page['info']['social_title'] ?? null))
                            <h2 class="hb-contact-social__title">{{ $page['info']['social_title'] }}</h2>
                        @endif
                        <div class="hb-contact-social__links">
                            @foreach($page['social'] as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" class="hb-contact-social__btn hb-contact-social__btn--{{ $social['icon'] ?? 'facebook' }}" aria-label="{{ $social['label'] ?? '' }}">
                                    @if(($social['icon'] ?? '') === 'facebook')
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    @elseif(($social['icon'] ?? '') === 'instagram')
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.919-.02.08 1.58.06 3.16-.06 4.74-.94 5.06-5.04 8.98-9.86 9.04-4.82.06-9.14-3.98-9.2-9.78-.06-5.8 4.36-9.86 9.14-9.98z"/></svg>
                                    @endif
                                    <span>{{ $social['label'] ?? '' }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="hb-contact-form-wrap hb-contact-reveal" data-delay="100">
                <div class="hb-contact-form-card">
                    <div class="hb-contact-form-card__glow" aria-hidden="true"></div>
                    @if(filled($page['form']['title'] ?? null))
                        <h2 class="hb-contact-form__title">{{ $page['form']['title'] }}</h2>
                    @endif
                    @if(filled($page['form']['subtitle'] ?? null))
                        <p class="hb-contact-form__subtitle">{{ $page['form']['subtitle'] }}</p>
                    @endif

                    <form action="{{ route('shop.contact.store') }}" method="post" class="hb-contact-form" data-contact-form>
                        @csrf
                        <div class="hb-contact-form__grid">
                            <label class="hb-contact-field">
                                <span>{{ __('ecommerce.contact_form_name') }}</span>
                                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" placeholder="{{ __('ecommerce.contact_form_name') }}">
                                @error('name')<em class="hb-contact-field__error">{{ $message }}</em>@enderror
                            </label>
                            <label class="hb-contact-field">
                                <span>{{ __('ecommerce.contact_form_phone') }}</span>
                                <input type="tel" name="phone" value="{{ old('phone') }}" required maxlength="50" autocomplete="tel" dir="ltr" placeholder="+20 ...">
                                @error('phone')<em class="hb-contact-field__error">{{ $message }}</em>@enderror
                            </label>
                        </div>
                        <label class="hb-contact-field">
                            <span>{{ __('ecommerce.contact_form_email') }}</span>
                            <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" dir="ltr" placeholder="email@example.com">
                            @error('email')<em class="hb-contact-field__error">{{ $message }}</em>@enderror
                        </label>
                        <label class="hb-contact-field">
                            <span>{{ __('ecommerce.contact_form_message') }}</span>
                            <textarea name="message" rows="5" required maxlength="5000" placeholder="{{ __('ecommerce.contact_form_message_placeholder') }}">{{ old('message') }}</textarea>
                            @error('message')<em class="hb-contact-field__error">{{ $message }}</em>@enderror
                        </label>
                        <button type="submit" class="hb-contact-form__submit">
                            <span>{{ __('ecommerce.contact_form_submit') }}</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15m0 0l6.75 6.75M4.5 12l6.75-6.75"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    @if(!empty($page['map']['is_active']) && filled($page['map']['embed_url'] ?? null))
        <section class="hb-contact-map hb-contact-reveal" aria-label="{{ __('ecommerce.contact_map') }}">
            <div class="hb-contact-map__header">
                <h2>{{ __('ecommerce.contact_map') }}</h2>
                <p>{{ $page['info']['address'] ?? '' }}</p>
            </div>
            <div class="hb-contact-map__inner">
                <iframe
                    src="{{ $page['map']['embed_url'] }}"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                    title="{{ __('ecommerce.contact_map') }}"
                ></iframe>
                @if(filled($page['map']['link_url'] ?? null))
                    <a href="{{ $page['map']['link_url'] }}" target="_blank" rel="noopener noreferrer" class="hb-contact-map__link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        {{ __('ecommerce.contact_open_in_maps') }}
                    </a>
                @endif
            </div>
        </section>
    @endif
@endsection
