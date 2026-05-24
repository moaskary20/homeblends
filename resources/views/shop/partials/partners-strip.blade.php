@php
    $partners = $partners ?? [];
@endphp

@if(!empty($partners))
    <section class="hb-partners" aria-label="{{ __('ecommerce.success_partners') }}">
        <p class="hb-partners-label">{{ __('ecommerce.success_partners') }}</p>
        <div class="overflow-hidden">
            <div class="hb-partners-track">
                @foreach(array_merge($partners, $partners) as $partner)
                    <div class="hb-partner-item">
                        @if(!empty($partner['logo']) && ($logoUrl = \App\Services\Shop\HomepageService::partnerLogoUrl($partner['logo'])))
                            <span class="hb-partner-circle">
                                <img src="{{ $logoUrl }}" alt="{{ $partner['name'] ?? '' }}" loading="lazy" decoding="async">
                            </span>
                        @else
                            {{ $partner['name'] ?? '' }}
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
