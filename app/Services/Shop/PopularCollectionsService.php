<?php

namespace App\Services\Shop;

use App\Models\Product;
use App\Support\ProductMedia;
use Illuminate\Support\Collection;

class PopularCollectionsService
{
    public function __construct(
        protected HomepageService $homepage,
    ) {}

    /**
     * @return Collection<int, array{
     *     title: string,
     *     items_count: int,
     *     shop_url: string,
     *     hero: array{image: ?string, name: string, url: string},
     *     thumbs: array<int, array{image: ?string, name: string, url: string}>
     * }>
     */
    public function cards(): Collection
    {
        $config = $this->homepage->resolvePopularCollections();
        $items = $config['items'] ?? [];

        if ($this->settingsUseProducts($items)) {
            $cards = $this->cardsFromSettings($items);
        } else {
            $cards = $this->cardsFromCatalog();
        }

        return $cards->filter(fn (array $card): bool => filled($card['hero']['image']))->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function settingsUseProducts(array $items): bool
    {
        return collect($items)->contains(fn (array $item): bool => filled($item['product_id'] ?? null));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int, array<string, mixed>>
     */
    protected function cardsFromSettings(array $items): Collection
    {
        $productIds = collect($items)
            ->flatMap(fn (array $item): array => array_filter([
                $item['product_id'] ?? null,
                ...($item['product_ids'] ?? []),
            ]))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $products = Product::query()
            ->with(['category', 'images', 'activeFlashSaleEntry.flashSale'])
            ->published()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return collect($items)
            ->map(function (array $item) use ($products): ?array {
                $hero = $products->get((int) ($item['product_id'] ?? 0));
                if (! $hero) {
                    return null;
                }

                $thumbIds = collect($item['product_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0 && $id !== $hero->id)
                    ->unique()
                    ->take(3)
                    ->values();

                $thumbProducts = $thumbIds
                    ->map(fn (int $id) => $products->get($id))
                    ->filter();

                return $this->buildCard(
                    $hero,
                    $thumbProducts,
                    $item['title'] ?? null,
                    isset($item['items_count']) ? (int) $item['items_count'] : null,
                    $item['url'] ?? null,
                );
            })
            ->filter();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function cardsFromCatalog(): Collection
    {
        $products = Product::query()
            ->with(['category', 'images', 'activeFlashSaleEntry.flashSale'])
            ->published()
            ->featured()
            ->latest()
            ->limit(24)
            ->get();

        if ($products->count() < 4) {
            $products = Product::query()
                ->with(['category', 'images', 'activeFlashSaleEntry.flashSale'])
                ->published()
                ->latest()
                ->limit(24)
                ->get();
        }

        return $products
            ->groupBy('category_id')
            ->map(function (Collection $group): array {
                $hero = $group->first();
                $thumbProducts = $group->where('id', '!=', $hero->id)->take(3)->values();

                return $this->buildCard($hero, $thumbProducts);
            })
            ->values()
            ->take(8);
    }

    /**
     * @param  Collection<int, Product>  $thumbProducts
     * @return array<string, mixed>
     */
    protected function buildCard(
        Product $hero,
        Collection $thumbProducts,
        ?string $titleOverride = null,
        ?int $itemsCountOverride = null,
        ?string $urlOverride = null,
    ): array {
        $category = $hero->category;
        $title = $titleOverride ?: $hero->name;

        $itemsCount = $itemsCountOverride ?? ($category
            ? Product::query()->published()->where('category_id', $category->id)->count()
            : 1);

        $shopUrl = filled($urlOverride)
            ? url($urlOverride)
            : route('shop.products.show', $hero->slug);

        $thumbs = $this->resolveThumbs($hero, $thumbProducts);

        return [
            'title' => $title,
            'items_count' => max(1, $itemsCount),
            'shop_url' => $shopUrl,
            'hero' => $this->productSlide($hero),
            'thumbs' => $thumbs,
        ];
    }

    /**
     * @param  Collection<int, Product>  $thumbProducts
     * @return array<int, array{image: ?string, name: string, url: string}>
     */
    protected function resolveThumbs(Product $hero, Collection $thumbProducts): array
    {
        $thumbs =         $thumbProducts
            ->map(fn (Product $product): array => $this->productSlide($product, ProductMedia::SIZE_COLLECTION_THUMB))
            ->filter(fn (array $slide): bool => filled($slide['image']))
            ->values()
            ->all();

        if (count($thumbs) >= 3) {
            return array_slice($thumbs, 0, 3);
        }

        $gallery = ProductMedia::productGallery($hero)
            ->map(fn (array $item): array => [
                'image' => filled($item['path'] ?? null)
                    ? ProductMedia::resizeUrl($item['path'], ProductMedia::SIZE_COLLECTION_THUMB)
                    : $item['url'],
                'name' => $item['alt'],
                'url' => route('shop.products.show', $hero->slug),
            ])
            ->filter(fn (array $slide): bool => filled($slide['image']))
            ->values();

        foreach ($gallery as $slide) {
            if (count($thumbs) >= 3) {
                break;
            }

            if ($slide['image'] === ($thumbs[0]['image'] ?? null) || $slide['image'] === ($this->productSlide($hero)['image'] ?? null)) {
                continue;
            }

            $alreadyUsed = collect($thumbs)->contains(fn (array $t): bool => $t['image'] === $slide['image']);
            if (! $alreadyUsed) {
                $thumbs[] = $slide;
            }
        }

        while (count($thumbs) < 3) {
            $thumbs[] = $this->productSlide($hero);
        }

        return array_slice($thumbs, 0, 3);
    }

    /**
     * @return array{image: ?string, name: string, url: string}
     */
    protected function productSlide(Product $product, ?int $width = null): array
    {
        $width ??= ProductMedia::SIZE_COLLECTION_HERO;

        return [
            'image' => ProductMedia::productThumbnail($product, $width),
            'name' => $product->name,
            'url' => route('shop.products.show', $product->slug),
        ];
    }
}
