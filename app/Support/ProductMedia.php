<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductMedia
{
    /** Maximum images shown in product gallery (avoids layout blowout on scraped products). */
    public const GALLERY_LIMIT = 20;

    public const SIZE_CARD = 480;

    public const SIZE_SWATCH = 80;

    public const SIZE_COLLECTION_HERO = 680;

    public const SIZE_COLLECTION_THUMB = 200;

    public const SIZE_REVIEW = 360;

    public const SIZE_SPOTLIGHT_HERO = 960;

    public const SIZE_SPOTLIGHT_THUMB = 144;

    public const SIZE_BANNER = 1280;

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    public static function resizeUrl(?string $path, int $width): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return self::optimizeRemoteUrl($path, $width);
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, 8);
        }

        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $width = max(40, min(1600, $width));

        return url('/media/'.$width.'/'.$relative);
    }

    public static function optimizeRemoteUrl(string $url, int $width): string
    {
        if (str_contains($url, 'images.unsplash.com')) {
            $parsed = parse_url($url);
            parse_str($parsed['query'] ?? '', $query);
            $query['w'] = min($width, 1600);
            $query['q'] = $query['q'] ?? 75;
            $query['auto'] = 'format';

            return ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '').($parsed['path'] ?? '').'?'.http_build_query($query);
        }

        return $url;
    }

    /**
     * Main image URL, then first gallery image.
     */
    public static function productThumbnail(Product $product, ?int $width = null): ?string
    {
        $path = $product->main_image;

        if (! $path && $product->relationLoaded('images') && $product->images->isNotEmpty()) {
            $path = $product->images->first()->path;
        }

        if (! $path) {
            return null;
        }

        return $width ? self::resizeUrl($path, $width) : self::url($path);
    }

    /**
     * @return Collection<int, array{url: string, alt: string, path: ?string}>
     */
    public static function productGallery(Product $product): Collection
    {
        $items = collect();
        $seen = [];

        if ($product->main_image) {
            $url = self::url($product->main_image);
            if ($url) {
                $items->push([
                    'url' => $url,
                    'alt' => $product->name,
                    'path' => $product->main_image,
                ]);
                $seen[$url] = true;
            }
        }

        if ($product->relationLoaded('images')) {
            foreach ($product->images as $image) {
                $url = self::url($image->path);
                if ($url && ! isset($seen[$url])) {
                    $items->push([
                        'url' => $url,
                        'alt' => $image->alt ?: $product->name,
                        'path' => $image->path,
                    ]);
                    $seen[$url] = true;
                }
            }
        }

        return $items->take(self::GALLERY_LIMIT)->values();
    }

    public static function productGalleryTotal(Product $product): int
    {
        $count = 0;
        $seen = [];

        if ($product->main_image) {
            $url = self::url($product->main_image);
            if ($url) {
                $count++;
                $seen[$url] = true;
            }
        }

        if ($product->relationLoaded('images')) {
            foreach ($product->images as $image) {
                $url = self::url($image->path);
                if ($url && ! isset($seen[$url])) {
                    $count++;
                    $seen[$url] = true;
                }
            }
        }

        return $count;
    }

    /**
     * Catalog / variant thumbnails for homepage showcase cards.
     *
     * @return Collection<int, array{url: string, variant_id: int|null}>
     */
    public static function catalogSwatches(Product $product, int $limit = 5, ?int $width = self::SIZE_SWATCH): Collection
    {
        $items = collect();
        $seen = [];

        if ($product->relationLoaded('variants')) {
            foreach ($product->variants as $variant) {
                $url = $width
                    ? self::resizeUrl($variant->image, $width)
                    : self::url($variant->image);
                if ($url && ! isset($seen[$url])) {
                    $items->push(['url' => $url, 'variant_id' => $variant->id]);
                    $seen[$url] = true;
                }
            }
        }

        if ($items->count() < $limit) {
            foreach (self::productGallery($product) as $image) {
                if ($items->count() >= $limit) {
                    break;
                }
                $url = filled($image['path'] ?? null)
                    ? self::resizeUrl($image['path'], $width ?? self::SIZE_SWATCH)
                    : $image['url'];
                if ($url && ! isset($seen[$url])) {
                    $items->push(['url' => $url, 'variant_id' => null]);
                    $seen[$url] = true;
                }
            }
        }

        return $items->take($limit)->values();
    }
}
