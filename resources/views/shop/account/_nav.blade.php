@php
    $current = $current ?? '';
@endphp
<aside class="hb-account-nav">
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
        <p class="font-bold text-[#3d3830]">{{ auth()->user()->name }}</p>
        <p class="text-sm text-gray-500 truncate">{{ auth()->user()->email }}</p>
    </div>
    <nav class="bg-white rounded-xl shadow-sm overflow-hidden">
        @foreach([
            'profile' => ['route' => 'shop.account.profile', 'label' => __('ecommerce.my_account'), 'icon' => '👤'],
            'purchases' => ['route' => 'shop.account.purchases', 'label' => __('ecommerce.my_purchases'), 'icon' => '🛍️'],
            'points' => ['route' => 'shop.account.points', 'label' => __('ecommerce.my_points'), 'icon' => '⭐'],
            'tracking' => ['route' => 'shop.account.tracking', 'label' => __('ecommerce.track_orders'), 'icon' => '📦'],
            'favorites' => ['route' => 'shop.account.favorites', 'label' => __('ecommerce.my_favorites'), 'icon' => '❤️'],
            'compare' => ['route' => 'shop.account.compare', 'label' => __('ecommerce.my_compare'), 'icon' => '⚖️'],
        ] as $key => $item)
            <a href="{{ route($item['route']) }}"
               class="hb-account-nav-link {{ $current === $key ? 'is-active' : '' }}">
                <span>{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
        <form method="post" action="{{ route('logout') }}" class="border-t">
            @csrf
            <button type="submit" class="hb-account-nav-link w-full text-red-600 hover:bg-red-50">
                <span>🚪</span>
                <span>{{ __('ecommerce.logout') }}</span>
            </button>
        </form>
    </nav>
</aside>
