@php
    $review = $review ?? [];
    $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
@endphp

<article class="hb-cr-card">
    <div class="hb-cr-card__shell">
        <div class="hb-cr-card__media">
            @if(!empty($review['image']))
                <img
                    src="{{ $review['image'] }}"
                    alt=""
                    loading="lazy"
                    decoding="async"
                >
            @endif
            <div class="hb-cr-card__rating" aria-label="{{ __('ecommerce.rating_of', ['rating' => $rating, 'max' => 5]) }}">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="hb-cr-star {{ $i <= $rating ? 'is-filled' : '' }}" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>
        </div>
        <div class="hb-cr-card__body">
            <p class="hb-cr-card__name">
                @if(!empty($review['is_verified']))
                    <span class="hb-cr-verified" title="{{ __('ecommerce.verified_purchase') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                    </span>
                @endif
                <span>{{ $review['customer_name'] ?? '' }}</span>
            </p>
            @if(filled($review['comment'] ?? null))
                <p class="hb-cr-card__text">{{ $review['comment'] }}</p>
            @endif
        </div>
    </div>
</article>
