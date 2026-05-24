@php
    $timeline = $timeline ?? new \App\Support\OrderTrackingTimeline($order);
@endphp

@if($timeline->showRouteMap())
    <div class="hb-tracking-route" style="--hb-tracking-progress: {{ $timeline->progressPercent() }}%">
        <div class="hb-tracking-route-track" aria-hidden="true">
            <div class="hb-tracking-route-fill"></div>
        </div>
        <ol class="hb-tracking-route-steps">
            @foreach($timeline->routeSteps() as $step)
                <li class="hb-tracking-route-step is-{{ $step['state'] }}">
                    <span class="hb-tracking-route-node" aria-hidden="true">
                        <span class="hb-tracking-route-icon">{{ $step['icon'] }}</span>
                    </span>
                    <div class="hb-tracking-route-meta">
                        <p class="hb-tracking-route-label">{{ $step['label'] }}</p>
                        @if($step['reached_at'])
                            <time class="hb-tracking-route-time" datetime="{{ $step['reached_at']->toIso8601String() }}">
                                {{ $step['reached_at']->format('d/m/Y H:i') }}
                            </time>
                        @elseif($step['state'] === 'current')
                            <span class="hb-tracking-route-badge">{{ __('ecommerce.tracking_in_progress') }}</span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
@elseif($timeline->isTerminal())
    <div class="hb-tracking-terminal is-{{ $order->status->value }}">
        <span class="hb-tracking-terminal-icon" aria-hidden="true">{{ $timeline->terminalIcon() }}</span>
        <p class="hb-tracking-terminal-label">{{ $timeline->terminalLabel() }}</p>
    </div>
@endif
