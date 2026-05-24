<?php

namespace App\Services\Shop;

use App\Models\Product;
use App\Models\Setting;
use App\Support\ProductMedia;
use Illuminate\Support\Collection;

class CustomerReviewsService
{
    /**
     * @return Collection<int, array{
     *     image: ?string,
     *     customer_name: string,
     *     rating: int,
     *     comment: string,
     *     is_verified: bool
     * }>
     */
    public function cards(): Collection
    {
        $config = Setting::getValue('homepage_customer_reviews', config('homepage.customer_reviews', []));

        if (! is_array($config) || ! ($config['is_active'] ?? true)) {
            return collect();
        }

        $items = $config['items'] ?? [];
        if (is_array($items) && count($items) > 0) {
            return collect($items)
                ->map(fn (array $item): array => $this->mapItem($item))
                ->filter(fn (array $item): bool => filled($item['image']) && filled($item['comment']))
                ->values();
        }

        return $this->autoCards((int) ($config['auto_limit'] ?? 10));
    }

    public function sectionTitle(): string
    {
        $config = Setting::getValue('homepage_customer_reviews', config('homepage.customer_reviews', []));

        return filled($config['section_title'] ?? null)
            ? (string) $config['section_title']
            : (string) (config('homepage.customer_reviews.section_title') ?? __('ecommerce.customer_reviews'));
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function mapItem(array $item): array
    {
        $image = HomepageService::mediaUrl($item['image'] ?? null, ProductMedia::SIZE_REVIEW);
        $productId = (int) ($item['product_id'] ?? 0);

        if (! $image && $productId > 0) {
            $product = Product::query()->published()->find($productId);
            if ($product) {
                $image = ProductMedia::productThumbnail($product, ProductMedia::SIZE_REVIEW);
            }
        }

        return [
            'image' => $image,
            'customer_name' => (string) ($item['customer_name'] ?? ''),
            'rating' => max(1, min(5, (int) ($item['rating'] ?? 5))),
            'comment' => (string) ($item['comment'] ?? ''),
            'is_verified' => (bool) ($item['is_verified'] ?? false),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function autoCards(int $limit): Collection
    {
        $limit = max(4, min(16, $limit));
        $samples = config('homepage.customer_reviews.samples', []);

        return Product::query()
            ->published()
            ->whereNotNull('main_image')
            ->with(['images', 'variants'])
            ->latest()
            ->limit($limit)
            ->get()
            ->values()
            ->map(function (Product $product, int $index) use ($samples): array {
                $sample = $samples[$index % count($samples)] ?? [];

                return [
                    'image' => ProductMedia::productThumbnail($product, ProductMedia::SIZE_REVIEW),
                    'customer_name' => (string) ($sample['customer_name'] ?? __('ecommerce.customer_reviews_guest')),
                    'rating' => max(1, min(5, (int) ($sample['rating'] ?? 5))),
                    'comment' => (string) ($sample['comment'] ?? $product->name),
                    'is_verified' => (bool) ($sample['is_verified'] ?? true),
                ];
            })
            ->filter(fn (array $item): bool => filled($item['image']))
            ->values();
    }
}
