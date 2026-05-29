<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Support\AppUrl;
use App\Support\ProductMedia;

class ComfortSpotlightService
{
    /**
     * @return array<string, mixed>|null
     */
    public function resolve(): ?array
    {
        $config = Setting::getValue('homepage_comfort_spotlight', config('homepage.comfort_spotlight', []));

        if (! is_array($config) || ! ($config['is_active'] ?? true)) {
            return null;
        }

        $defaults = config('homepage.comfort_spotlight', []);
        $imageUrl = $this->resolveHeroImage($config, $defaults);
        $links = $this->resolveLinks($config, $defaults);
        $thumbs = $this->resolveThumbs($config, $defaults);

        if (! $imageUrl && $thumbs !== []) {
            $imageUrl = $thumbs[0]['image'] ?? null;
        }

        if (! $imageUrl && $thumbs === []) {
            return null;
        }

        return AppUrl::normalizeComfortSpotlight([
            'eyebrow' => (string) ($config['eyebrow'] ?? $defaults['eyebrow'] ?? ''),
            'title' => (string) ($config['title'] ?? $defaults['title'] ?? ''),
            'description' => (string) ($config['description'] ?? $defaults['description'] ?? ''),
            'cta' => (string) ($config['cta'] ?? $defaults['cta'] ?? __('ecommerce.shop_all')),
            'url' => (string) ($config['url'] ?? $defaults['url'] ?? route('shop.products.index')),
            'image_url' => $imageUrl,
            'links' => $links,
            'thumbs' => $thumbs,
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $defaults
     */
    protected function resolveHeroImage(array $config, array $defaults): ?string
    {
        $image = HomepageService::mediaUrl($config['image'] ?? null, ProductMedia::SIZE_BANNER);
        if ($image) {
            return $image;
        }

        $productId = (int) ($config['hero_product_id'] ?? 0);
        if ($productId > 0) {
            $product = Product::query()->published()->find($productId);

            return $product ? ProductMedia::productThumbnail($product, ProductMedia::SIZE_SPOTLIGHT_HERO) : null;
        }

        $fallbackId = (int) ($defaults['hero_product_id'] ?? 0);
        if ($fallbackId > 0) {
            $product = Product::query()->published()->find($fallbackId);

            return $product ? ProductMedia::productThumbnail($product, ProductMedia::SIZE_SPOTLIGHT_HERO) : null;
        }

        $product = Product::query()
            ->published()
            ->whereNotNull('main_image')
            ->latest()
            ->first();

        return $product ? ProductMedia::productThumbnail($product, ProductMedia::SIZE_SPOTLIGHT_HERO) : null;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $defaults
     * @return array<int, array{name: string, url: string}>
     */
    protected function resolveLinks(array $config, array $defaults): array
    {
        $links = collect($config['links'] ?? $defaults['links'] ?? [])
            ->map(function (array $link): array {
                $categoryId = (int) ($link['category_id'] ?? 0);
                $url = (string) ($link['url'] ?? '');

                if ($categoryId > 0) {
                    $category = Category::query()->active()->find($categoryId);
                    if ($category) {
                        return [
                            'name' => (string) ($link['name'] ?? $category->name),
                            'url' => route('shop.categories.show', $category->slug),
                        ];
                    }
                }

                return [
                    'name' => (string) ($link['name'] ?? ''),
                    'url' => filled($url) ? $url : '#',
                ];
            })
            ->filter(fn (array $link): bool => filled($link['name']))
            ->values()
            ->all();

        return $links;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $defaults
     * @return array<int, array{image: ?string, url: string, name: string}>
     */
    protected function resolveThumbs(array $config, array $defaults): array
    {
        $productIds = collect($config['product_ids'] ?? $defaults['product_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->take(4)
            ->values();

        if ($productIds->isEmpty()) {
            $productIds = Product::query()
                ->published()
                ->whereNotNull('main_image')
                ->latest()
                ->limit(4)
                ->pluck('id');
        }

        return $productIds
            ->map(function (int $id): ?array {
                $product = Product::query()->published()->with(['variants', 'images'])->find($id);
                if (! $product) {
                    return null;
                }

                $image = ProductMedia::productThumbnail($product, ProductMedia::SIZE_SPOTLIGHT_THUMB);
                if (! $image) {
                    return null;
                }

                return [
                    'image' => $image,
                    'url' => route('shop.products.show', $product->slug),
                    'name' => $product->name,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
