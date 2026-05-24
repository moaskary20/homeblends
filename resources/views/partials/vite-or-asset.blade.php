@php
    $entries = $entries ?? [];
    $manifestPath = public_path('build/manifest.json');
    $useVite = file_exists(public_path('hot'));

    if (! $useVite && file_exists($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true) ?? [];
        $useVite = collect($entries)->every(fn (string $entry): bool => isset($manifest[$entry]));
    }
@endphp

@if ($useVite)
    @vite($entries)
@else
    @foreach ($css ?? [] as $href)
        <link rel="stylesheet" href="{{ asset($href) }}">
    @endforeach
    @foreach ($scripts ?? [] as $src)
        <script src="{{ asset($src) }}" defer></script>
    @endforeach
@endif
