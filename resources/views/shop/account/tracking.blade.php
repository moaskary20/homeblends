@extends('shop.account._layout', ['current' => 'tracking'])

@section('account_content')
    <div class="hb-tracking-page">
        <header class="hb-tracking-page-header">
            <div>
                <h1 class="hb-tracking-title">{{ __('ecommerce.track_orders') }}</h1>
                <p class="hb-tracking-subtitle">{{ __('ecommerce.tracking_page_hint') }}</p>
            </div>
        </header>

        @if($orders->isEmpty())
            <div class="hb-tracking-empty">
                <span class="hb-tracking-empty-icon" aria-hidden="true">📦</span>
                <p>{{ __('ecommerce.tracking_empty') }}</p>
                <a href="{{ route('shop.products.index') }}" class="hb-account-btn-primary">{{ __('ecommerce.explore_now') }}</a>
            </div>
        @else
            <div class="hb-tracking-orders">
                @foreach($orders as $order)
                    @php($timeline = new \App\Support\OrderTrackingTimeline($order))
                    <article class="hb-tracking-order" id="order-{{ $order->order_number }}">
                        <header class="hb-tracking-order-header">
                            <div class="hb-tracking-order-meta">
                                <a href="{{ route('shop.orders.show', $order->order_number) }}" class="hb-tracking-order-number">
                                    {{ $order->order_number }}
                                </a>
                                <time class="hb-tracking-order-date" datetime="{{ $order->created_at->toIso8601String() }}">
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </time>
                            </div>
                            <div class="hb-tracking-order-aside">
                                <span class="hb-tracking-status-pill is-{{ $order->status->value }}">
                                    {{ $order->status->label() }}
                                </span>
                                <p class="hb-tracking-order-total">
                                    {{ number_format($order->total, 2) }} {{ __('ecommerce.currency') }}
                                </p>
                            </div>
                        </header>

                        @if($order->tracking_number)
                            <div class="hb-tracking-number-box">
                                <div>
                                    <p class="hb-tracking-number-label">{{ __('ecommerce.tracking_number') }}</p>
                                    <p class="hb-tracking-number-value" dir="ltr">{{ $order->tracking_number }}</p>
                                </div>
                                <button type="button" class="hb-tracking-copy-btn" data-copy="{{ $order->tracking_number }}">
                                    {{ __('ecommerce.copy_link') }}
                                </button>
                            </div>
                        @endif

                        <div class="hb-tracking-map-section">
                            <h3 class="hb-tracking-map-title">{{ __('ecommerce.order_tracking') }}</h3>
                            @include('shop.partials.order-tracking-route', ['order' => $order, 'timeline' => $timeline])
                        </div>

                        @include('shop.partials.order-tracking-log', [
                            'order' => $order,
                            'timeline' => $timeline,
                            'open' => $loop->first,
                        ])

                        <footer class="hb-tracking-order-footer">
                            <a href="{{ route('shop.orders.show', $order->order_number) }}" class="hb-tracking-details-link">
                                {{ __('ecommerce.view_order_details') }} ←
                            </a>
                        </footer>
                    </article>
                @endforeach
            </div>
            <div class="hb-tracking-pagination">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.hb-tracking-copy-btn').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const text = btn.dataset.copy;
                if (!text) return;
                try {
                    await navigator.clipboard.writeText(text);
                    const prev = btn.textContent;
                    btn.textContent = @json(__('ecommerce.copied'));
                    setTimeout(() => { btn.textContent = prev; }, 2000);
                } catch {
                    window.prompt(@json(__('ecommerce.copy_link')), text);
                }
            });
        });
    </script>
@endpush
