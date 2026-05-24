@php
    $timeline = $timeline ?? new \App\Support\OrderTrackingTimeline($order);
    $log = $timeline->historyLog();
@endphp

@if($log->isNotEmpty())
    <details class="hb-tracking-log" @if($open ?? false) open @endif>
        <summary class="hb-tracking-log-summary">
            {{ __('ecommerce.tracking_admin_log') }}
            <span class="hb-tracking-log-count">{{ $log->count() }}</span>
        </summary>
        <ol class="hb-tracking-log-list">
            @foreach($log as $entry)
                <li class="hb-tracking-log-item">
                    <div class="hb-tracking-log-dot" aria-hidden="true"></div>
                    <div class="hb-tracking-log-body">
                        <div class="hb-tracking-log-top">
                            <span class="hb-tracking-log-status">{{ $entry['status_label'] }}</span>
                            <time datetime="{{ $entry['created_at']->toIso8601String() }}">
                                {{ $entry['created_at']->format('d/m/Y H:i') }}
                            </time>
                        </div>
                        @if($entry['comment'])
                            <p class="hb-tracking-log-comment">{{ $entry['comment'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </details>
@else
    <p class="hb-tracking-no-log">{{ __('ecommerce.no_tracking_yet') }}</p>
@endif
