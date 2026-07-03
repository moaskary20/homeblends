<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Shop\CategoryBrowseService;
use App\Services\Shop\CustomerReviewsService;
use App\Services\Shop\HomepageService;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke(
        HomepageService $homepage,
        CustomerReviewsService $customerReviews,
        ProductRepositoryInterface $products,
        CategoryRepositoryInterface $categories,
        CategoryBrowseService $categoryBrowse,
    ) {
        $payload = Cache::remember('api.v1.home', 1800, function () use (
            $homepage,
            $customerReviews,
            $products,
            $categories,
            $categoryBrowse,
        ) {
            $content = $homepage->getContent();
            $featuredLimit = max(4, (int) config('homepage.featured_products_limit', 12));

            $heroSlides = collect($content['hero_slides'] ?? [])
                ->map(fn (array $slide): array => [
                    'title' => (string) ($slide['title'] ?? ''),
                    'subtitle' => (string) ($slide['subtitle'] ?? ''),
                    'cta' => (string) ($slide['cta'] ?? __('Shop Now')),
                    'url' => (string) ($slide['url'] ?? ''),
                    'image' => HomepageService::slideImageUrl($slide['image'] ?? null, 1200),
                ])
                ->filter(fn (array $slide): bool => filled($slide['image']))
                ->values()
                ->all();

            $newsTicker = collect($content['news_ticker'] ?? [])
                ->map(fn ($item): string => is_array($item)
                    ? (string) ($item['text'] ?? '')
                    : (string) $item)
                ->filter(fn (string $text): bool => filled($text))
                ->values()
                ->all();

            $departmentSlugs = [
                ['title' => 'أثاث', 'slug' => 'athath'],
                ['title' => 'سيراميك', 'slug' => 'ceramics'],
                ['title' => 'صحي', 'slug' => 'sanitary'],
                ['title' => 'الأجهزة المنزلية', 'slug' => 'home-appliances'],
            ];

            $departments = collect($departmentSlugs)
                ->map(function (array $dept) use ($products, $categories, $categoryBrowse): ?array {
                    $category = $categories->findBySlug($dept['slug']);
                    if (! $category) {
                        return null;
                    }

                    $items = $products->paginate(12, [
                        'category_ids' => $categoryBrowse->categoryIdsIncludingChildren($category),
                    ]);

                    if ($items->isEmpty()) {
                        return null;
                    }

                    return [
                        'title' => $dept['title'],
                        'slug' => $dept['slug'],
                        'products' => ProductResource::collection($items->items())->resolve(),
                    ];
                })
                ->filter()
                ->values()
                ->all();

            return [
                'news_ticker' => $newsTicker,
                'hero_slides' => $heroSlides,
                'featured' => ProductResource::collection(
                    $products->getFeatured($featuredLimit)
                )->resolve(),
                'departments' => $departments,
                'customer_reviews' => [
                    'title' => $customerReviews->sectionTitle(),
                    'items' => $customerReviews->cards()->values()->all(),
                ],
            ];
        });

        return response()->json($payload);
    }
}
