@php
    $reviews = $customerReviewCards ?? collect();
    $sectionTitle = $customerReviewsTitle ?? __('ecommerce.customer_reviews');
@endphp

@if($reviews->isNotEmpty())
    <section class="hb-customer-reviews" id="customer-reviews" aria-labelledby="customer-reviews-title">
        <div class="hb-cr-inner">
            <h2 id="customer-reviews-title" class="hb-cr-title">{{ $sectionTitle }}</h2>

            <div class="hb-cr-slider">
                <button type="button" class="hb-cr-nav-btn hb-cr-prev" aria-label="{{ __('ecommerce.carousel_prev') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                </button>

                <div class="hb-cr-viewport">
                    <div class="hb-cr-track">
                        @foreach($reviews as $review)
                            @include('shop.partials.customer-review-card', ['review' => $review])
                        @endforeach
                    </div>
                </div>

                <button type="button" class="hb-cr-nav-btn hb-cr-next" aria-label="{{ __('ecommerce.carousel_next') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                </button>
            </div>
        </div>
    </section>
@endif
