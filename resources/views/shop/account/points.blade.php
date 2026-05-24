@extends('shop.account._layout', ['current' => 'points'])

@php
    $pointValue = (float) ($program['point_value'] ?? 0.1);
    $redeemValue = round($user->loyalty_points * $pointValue, 2);
    $storeCredit = (float) ($user->store_credit ?? 0);
    $minRedeem = (int) ($program['min_redeem_points'] ?? 10);
    $maxRedeem = (int) ($program['max_wallet_redeem_points'] ?? $user->loyalty_points);
    $canRedeem = $maxRedeem >= $minRedeem;
@endphp

@section('account_content')
    <div class="hb-points-page"
         data-point-value="{{ $pointValue }}"
         data-currency="{{ __('ecommerce.currency') }}"
         data-min-redeem="{{ $minRedeem }}"
         data-max-redeem="{{ $maxRedeem }}">

        <header class="hb-points-page-header">
            <div>
                <h1 class="hb-points-title">{{ __('ecommerce.my_points') }}</h1>
                <p class="hb-points-subtitle">{{ __('ecommerce.loyalty_program') }}</p>
            </div>
            <a href="{{ route('shop.products.index') }}" class="hb-account-btn-primary hb-points-cta">
                {{ __('ecommerce.points_shop_cta') }}
            </a>
        </header>

        <div class="hb-points-balances">
            <article class="hb-points-balance-card is-points">
                <span class="hb-points-balance-icon" aria-hidden="true">⭐</span>
                <div>
                    <p class="hb-points-balance-label">{{ __('ecommerce.loyalty_balance') }}</p>
                    <p class="hb-points-balance-value">{{ number_format($user->loyalty_points) }}</p>
                    <p class="hb-points-balance-meta">{{ __('ecommerce.points') }} · {{ __('ecommerce.points_redeem_value') }} {{ number_format($redeemValue, 2) }} {{ __('ecommerce.currency') }}</p>
                </div>
            </article>
            <article class="hb-points-balance-card is-wallet">
                <span class="hb-points-balance-icon" aria-hidden="true">💳</span>
                <div>
                    <p class="hb-points-balance-label">{{ __('ecommerce.store_credit') }}</p>
                    <p class="hb-points-balance-value">{{ number_format($storeCredit, 2) }}</p>
                    <p class="hb-points-balance-meta">{{ __('ecommerce.currency') }} · {{ __('ecommerce.store_credit_hint') }}</p>
                </div>
            </article>
        </div>

        @if($user->vipLevel)
            <p class="hb-points-vip-line">
                {{ __('ecommerce.vip_level') }}: <strong>{{ $user->vipLevel->name }}</strong>
                @if(($program['vip_discount_percent'] ?? 0) > 0)
                    — {{ __('ecommerce.vip_discount_percent') }} {{ $program['vip_discount_percent'] }}%
                @endif
            </p>
        @endif

        <div class="hb-points-grid">
            <section class="hb-points-redeem-panel">
                <h2 class="hb-points-panel-title">{{ __('ecommerce.points_redeem_to_wallet') }}</h2>
                <p class="hb-points-panel-hint">{{ __('ecommerce.points_redeem_to_wallet_hint') }}</p>

                @if($canRedeem)
                    <form method="post" action="{{ route('shop.account.points.redeem') }}" class="hb-points-redeem-form" id="points-redeem-form">
                        @csrf
                        <label class="hb-points-field-label" for="redeem-points">{{ __('ecommerce.points_redeem_amount') }}</label>
                        <div class="hb-points-redeem-row">
                            <input type="number"
                                   id="redeem-points"
                                   name="points"
                                   class="hb-points-input"
                                   min="{{ $minRedeem }}"
                                   max="{{ $maxRedeem }}"
                                   step="1"
                                   value="{{ old('points', $minRedeem) }}"
                                   required
                                   data-redeem-points-input>
                            <button type="button" class="hb-points-max-btn" data-redeem-max>{{ __('ecommerce.redeem_all_points') }}</button>
                        </div>
                        <p class="hb-points-redeem-hint">{{ __('ecommerce.points_redeem_max', ['max' => number_format($maxRedeem)]) }}</p>
                        @error('points')
                            <p class="hb-points-field-error">{{ $message }}</p>
                        @enderror
                        <div class="hb-points-preview-box">
                            <span>{{ __('ecommerce.points_redeem_preview') }}</span>
                            <strong data-redeem-preview-amount>0.00</strong>
                            <span>{{ __('ecommerce.currency') }}</span>
                        </div>
                        <button type="submit" class="hb-points-submit-btn">{{ __('ecommerce.points_redeem_submit') }}</button>
                    </form>
                @else
                    <div class="hb-points-redeem-disabled">
                        <p>{{ __('ecommerce.loyalty_min_redeem', ['min' => $minRedeem]) }}</p>
                        <a href="{{ route('shop.products.index') }}" class="hb-account-btn-primary">{{ __('ecommerce.points_shop_cta') }}</a>
                    </div>
                @endif
            </section>

            <section class="hb-points-info-panel">
                <h2 class="hb-points-panel-title">{{ __('ecommerce.points_how_to_earn') }}</h2>
                <ul class="hb-points-info-list">
                    <li>
                        <span>🛒</span>
                        <div>
                            <strong>{{ __('ecommerce.loyalty_earn_rate', ['amount' => $program['earn_per_currency']]) }}</strong>
                        </div>
                    </li>
                    <li>
                        <span>💰</span>
                        <div>
                            <strong>{{ __('ecommerce.points_point_value_label') }}</strong>
                            <p>{{ __('ecommerce.loyalty_redeem_hint') }}</p>
                        </div>
                    </li>
                    <li>
                        <span>🎁</span>
                        <div>
                            <strong>{{ __('ecommerce.points_min_redeem_label') }}</strong>
                            <p>{{ __('ecommerce.loyalty_min_redeem', ['min' => $minRedeem]) }}</p>
                        </div>
                    </li>
                    @if(($program['expiry_months'] ?? 0) > 0)
                        <li>
                            <span>⏳</span>
                            <div>
                                <strong>{{ __('ecommerce.points_expiry_note', ['months' => $program['expiry_months']]) }}</strong>
                            </div>
                        </li>
                    @endif
                </ul>
            </section>
        </div>

        <section class="hb-points-history">
            <h2 class="hb-points-history-title">{{ __('ecommerce.points_history') }}</h2>

            @if($transactions->isEmpty())
                <div class="hb-points-empty">
                    <span class="hb-points-empty-icon" aria-hidden="true">✨</span>
                    <p>{{ __('ecommerce.no_loyalty_transactions') }}</p>
                </div>
            @else
                <ul class="hb-points-tx-list">
                    @foreach($transactions as $tx)
                        @php
                            $isCredit = $tx->points >= 0;
                            $typeClass = match ($tx->type) {
                                'earn' => 'is-earn',
                                'redeem', 'wallet' => 'is-redeem',
                                'expire' => 'is-expire',
                                'adjust' => 'is-adjust',
                                default => 'is-default',
                            };
                            $typeIcon = match ($tx->type) {
                                'earn' => '➕',
                                'redeem' => '🎁',
                                'wallet' => '💳',
                                'expire' => '⏳',
                                'adjust' => '⚙️',
                                default => '•',
                            };
                        @endphp
                        <li class="hb-points-tx {{ $typeClass }}">
                            <div class="hb-points-tx-icon" aria-hidden="true">{{ $typeIcon }}</div>
                            <div class="hb-points-tx-body">
                                <div class="hb-points-tx-top">
                                    <span class="hb-points-tx-type">{{ $tx->typeLabel() }}</span>
                                    <time class="hb-points-tx-date" datetime="{{ $tx->created_at->toIso8601String() }}">
                                        {{ $tx->created_at->format('d/m/Y H:i') }}
                                    </time>
                                </div>
                                @if($tx->description)
                                    <p class="hb-points-tx-desc">{{ $tx->description }}</p>
                                @endif
                                @if($tx->order)
                                    <a href="{{ route('shop.orders.show', $tx->order->order_number) }}" class="hb-points-tx-order">
                                        {{ __('ecommerce.points_order_link') }} {{ $tx->order->order_number }}
                                    </a>
                                @endif
                            </div>
                            <div class="hb-points-tx-points {{ $isCredit ? 'is-credit' : 'is-debit' }}">
                                {{ $isCredit ? '+' : '' }}{{ number_format($tx->points) }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/shop-points.js') }}" defer></script>
@endpush
