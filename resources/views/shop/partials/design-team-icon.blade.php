@php
    $icon = $icon ?? 'clock';
@endphp

@switch($icon)
    @case('ruler')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20L20 4"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 17l2-2M11 13l2-2M15 9l2-2"/>
        </svg>
        @break
    @case('moodboard')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <rect x="4" y="5" width="16" height="14" rx="1.5"/>
            <path stroke-linecap="round" d="M8 9h5v5H8z"/>
            <path stroke-linecap="round" d="M14 11l3-2v6h-3z"/>
            <circle cx="9.5" cy="8.5" r="0.8" fill="currentColor" stroke="none"/>
        </svg>
        @break
    @case('cube')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/>
        </svg>
        @break
    @case('sofa')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 14v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 14V11a2 2 0 012-2h10a2 2 0 012 2v3"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 14h18"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 17v2M17 17v2"/>
            <rect x="9" y="5" width="6" height="4" rx="0.5"/>
        </svg>
        @break
    @case('voucher')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16v8H4z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 8v8M16 8v8"/>
            <path stroke-linecap="round" d="M10 11h1M13 11h1M10 14h1M13 14h1"/>
        </svg>
        @break
    @default
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <circle cx="12" cy="12" r="8"/>
            <path stroke-linecap="round" d="M12 8v4l3 2"/>
        </svg>
@endswitch
