@php
    $percent = (int) ($percent ?? 0);
    $message = (string) ($message ?? '');
    $source = (string) ($source ?? '');
    $active = (bool) ($active ?? false);
@endphp

<div @class([
    'rounded-xl border p-4 shadow-sm',
    'border-primary-300 bg-primary-50 dark:border-primary-700 dark:bg-primary-950/40' => $active,
    'border-success-300 bg-success-50 dark:border-success-700 dark:bg-success-950/40' => ! $active && $percent >= 100,
])>
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ __('ecommerce.scrape_progress_title') }}
            @if($source !== '')
                <span class="font-normal text-gray-600 dark:text-gray-300">— {{ $source }}</span>
            @endif
        </p>
        <span class="text-sm font-mono text-primary-700 dark:text-primary-300">{{ $percent }}%</span>
    </div>

    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
        <div
            class="h-full rounded-full bg-primary-600 transition-all duration-300 ease-out dark:bg-primary-400"
            style="width: {{ $percent }}%"
            role="progressbar"
            aria-valuenow="{{ $percent }}"
            aria-valuemin="0"
            aria-valuemax="100"
        ></div>
    </div>

    @if($message !== '')
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ $message }}</p>
    @endif
</div>
