<?php

namespace App\Services\Shop;

use App\Models\Setting;
use App\Support\ProductMedia;
use Illuminate\Support\Collection;

class HomepageService
{
    public function getContent(): array
    {
        return [
            'announcement' => Setting::getValue('homepage_announcement', config('homepage.announcement')),
            'top_links' => Setting::getValue('homepage_top_links', config('homepage.top_links')),
            'sub_links' => Setting::getValue('homepage_sub_links', config('homepage.sub_links')),
            'social' => Setting::getValue('homepage_social', config('homepage.social')),
            'news_ticker' => Setting::getValue('homepage_news_ticker', config('homepage.news_ticker')),
            'hero_slides' => Setting::getValue('homepage_hero_slides', config('homepage.hero_slides')),
            'partners' => Setting::getValue('homepage_partners', config('homepage.partners')),
            'popular_collections' => Setting::getValue('homepage_popular_collections', config('homepage.popular_collections')),
            'design_banner' => Setting::getValue('homepage_design_banner', config('homepage.design_banner')),
            'catalog_showcase' => Setting::getValue('homepage_catalog_showcase', config('homepage.catalog_showcase')),
            'catalog_showcase_furniture' => Setting::getValue('homepage_catalog_showcase_furniture', config('homepage.catalog_showcase_furniture')),
            'promo_banner' => Setting::getValue('homepage_promo_banner', config('homepage.promo_banner')),
            'customer_reviews' => Setting::getValue('homepage_customer_reviews', config('homepage.customer_reviews')),
            'contact_strip' => Setting::getValue('homepage_contact_strip', config('homepage.contact_strip')),
            'comfort_spotlight' => Setting::getValue('homepage_comfort_spotlight', config('homepage.comfort_spotlight')),
        ];
    }

    /**
     * @return array{is_active: bool, items: array<int, array{icon: string, title: string, text: string, url: string}>}
     */
    public function resolveContactStrip(): array
    {
        $defaults = config('homepage.contact_strip', []);
        $data = Setting::getValue('homepage_contact_strip', $defaults);

        if (! is_array($data)) {
            $data = $defaults;
        }

        $items = collect($data['items'] ?? $defaults['items'] ?? [])
            ->map(fn (array $item): array => [
                'icon' => (string) ($item['icon'] ?? 'chat'),
                'title' => (string) ($item['title'] ?? ''),
                'text' => (string) ($item['text'] ?? ''),
                'url' => (string) ($item['url'] ?? ''),
            ])
            ->filter(fn (array $item): bool => filled($item['title']) && filled($item['text']))
            ->values()
            ->all();

        return [
            'is_active' => (bool) ($data['is_active'] ?? $defaults['is_active'] ?? true),
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     is_active: bool,
     *     image_url: ?string,
     *     eyebrow: string,
     *     title: string,
     *     subtitle: string,
     *     cta: string,
     *     url: string
     * }
     */
    public function resolveDesignBanner(): array
    {
        $defaults = config('homepage.design_banner', []);
        $data = Setting::getValue('homepage_design_banner', $defaults);

        if (! is_array($data)) {
            $data = $defaults;
        }

        $image = $data['image'] ?? $defaults['image'] ?? 'images/banner01.png';

        return [
            'is_active' => (bool) ($data['is_active'] ?? $defaults['is_active'] ?? true),
            'image_url' => static::mediaUrl($image, ProductMedia::SIZE_BANNER),
            'eyebrow' => $data['eyebrow'] ?? $defaults['eyebrow'] ?? '',
            'title' => $data['title'] ?? $defaults['title'] ?? '',
            'subtitle' => $data['subtitle'] ?? $defaults['subtitle'] ?? '',
            'cta' => $data['cta'] ?? $defaults['cta'] ?? '',
            'url' => $data['url'] ?? $defaults['url'] ?? '#contact',
        ];
    }

    /**
     * @return array{is_active: bool, image_url: ?string, cta: string, url: string}
     */
    public function resolvePromoBanner(): array
    {
        $defaults = config('homepage.promo_banner', []);
        $data = Setting::getValue('homepage_promo_banner', $defaults);

        if (! is_array($data)) {
            $data = $defaults;
        }

        $image = $data['image'] ?? $defaults['image'] ?? 'images/s1.png';

        return [
            'is_active' => (bool) ($data['is_active'] ?? $defaults['is_active'] ?? true),
            'image_url' => static::mediaUrl($image, ProductMedia::SIZE_BANNER),
            'cta' => $data['cta'] ?? $defaults['cta'] ?? __('Shop Now'),
            'url' => $data['url'] ?? $defaults['url'] ?? route('shop.products.index'),
        ];
    }

    public function resolvePopularCollections(): array
    {
        $defaults = config('homepage.popular_collections', []);
        $data = Setting::getValue('homepage_popular_collections', $defaults);

        if (! is_array($data)) {
            $data = $defaults;
        }

        $items = $data['items'] ?? [];
        if (! is_array($items) || count($items) === 0) {
            $items = $defaults['items'] ?? [];
        }

        return [
            'section_title' => $data['section_title'] ?? $defaults['section_title'] ?? __('ecommerce.popular_collections'),
            'items' => is_array($items) ? array_values($items) : [],
        ];
    }

    public static function popularCollections(): array
    {
        return app(static::class)->resolvePopularCollections();
    }

    public function categoriesForHome(): Collection
    {
        return app(CategoryBrowseService::class)->categoriesForHome();
    }

    public static function mediaUrl(?string $path, ?int $width = null): ?string
    {
        if (blank($path)) {
            return null;
        }

        if ($width) {
            return ProductMedia::resizeUrl($path, $width);
        }

        return ProductMedia::url($path);
    }

    public static function partnerLogoUrl(?string $logo): ?string
    {
        return static::mediaUrl($logo, 120);
    }

    public static function slideImageUrl(?string $image, int $width = 1400): ?string
    {
        if (blank($image)) {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return ProductMedia::optimizeRemoteUrl($image, $width);
        }

        return static::mediaUrl($image, $width);
    }
}
