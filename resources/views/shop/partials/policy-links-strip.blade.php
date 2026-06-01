@php
    $policyLinks = $policyLinks ?? app(\App\Services\Shop\LegalPageService::class)->homepageLinks();
    $strip = app(\App\Services\Shop\LegalPageService::class)->homepageStrip();
@endphp

@if(($strip['is_active'] ?? true) && count($policyLinks) > 0)
    <section class="hb-policy-strip" aria-label="{{ __('ecommerce.policy_strip') }}">
        <div class="hb-policy-strip__backdrop" aria-hidden="true">
            <span class="hb-policy-strip__orb hb-policy-strip__orb--1"></span>
            <span class="hb-policy-strip__orb hb-policy-strip__orb--2"></span>
            <span class="hb-policy-strip__orb hb-policy-strip__orb--3"></span>
            <span class="hb-policy-strip__mesh"></span>
        </div>

        <div class="hb-policy-strip__inner">
            <header class="hb-policy-strip__head hb-policy-reveal">
                @if(filled($strip['eyebrow'] ?? null))
                    <p class="hb-policy-strip__eyebrow">
                        <span class="hb-policy-strip__eyebrow-line" aria-hidden="true"></span>
                        <span>{{ $strip['eyebrow'] }}</span>
                        <span class="hb-policy-strip__eyebrow-line" aria-hidden="true"></span>
                    </p>
                @endif
                <h2 class="hb-policy-strip__title">{{ $strip['title'] ?? __('ecommerce.policy_strip') }}</h2>
                @if(filled($strip['subtitle'] ?? null))
                    <p class="hb-policy-strip__subtitle">{{ $strip['subtitle'] }}</p>
                @endif
            </header>

            <div class="hb-policy-strip__grid">
                @foreach($policyLinks as $index => $link)
                    <a
                        href="{{ $link['url'] }}"
                        class="hb-policy-card hb-policy-card--{{ $link['key'] }} hb-policy-reveal"
                        data-delay="{{ $index * 100 }}"
                    >
                        <span class="hb-policy-card__glow" aria-hidden="true"></span>
                        <span class="hb-policy-card__border" aria-hidden="true"></span>

                        <span class="hb-policy-card__top">
                            <span class="hb-policy-card__index" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="hb-policy-card__icon" aria-hidden="true">
                                @include('shop.partials.policy-icon', ['icon' => $link['icon']])
                            </span>
                        </span>

                        <span class="hb-policy-card__body">
                            <span class="hb-policy-card__title">{{ $link['title'] }}</span>
                            @if(filled($link['excerpt'] ?? null))
                                <span class="hb-policy-card__excerpt">{{ \Illuminate\Support\Str::limit($link['excerpt'], 88) }}</span>
                            @endif
                        </span>

                        <span class="hb-policy-card__footer">
                            <span class="hb-policy-card__cta">{{ __('ecommerce.read_policy') }}</span>
                            <span class="hb-policy-card__arrow" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
