<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Bundle\BundleService;
use App\Services\Shop\CategoryBrowseService;
use App\Services\Shop\HomepageService;
use App\Services\Shop\CatalogShowcaseService;
use App\Services\Shop\ComfortSpotlightService;
use App\Services\Shop\CustomerReviewsService;
use App\Services\Shop\PopularCollectionsService;
use App\Services\Seo\SeoService;
use App\Services\FlashSale\FlashSaleService;
use App\Support\AppUrl;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke(
        ProductRepositoryInterface $products,
        CategoryRepositoryInterface $categories,
    ) {
        $featuredLimit = max(1, (int) config('homepage.featured_products_limit', 12));
        $featured = Cache::remember('shop.featured', 3600, fn () => $products->getFeatured($featuredLimit));
        $categoryTree = Cache::remember('shop.categories', 7200, fn () => $categories->getTree());
        $flashProducts = app(FlashSaleService::class)->getHighlightedProducts(8);
        $flashSales = app(FlashSaleService::class)->getActiveSales();
        $bundles = app(BundleService::class)->getActiveBundles()->take(4);

        $seo = app(SeoService::class)->forHome();
        $homepageService = app(HomepageService::class);
        $homepage = $homepageService->getContent();
        $popularCollectionCards = AppUrl::rewriteCachedValue(Cache::remember(
            'shop.popular_collections',
            3600,
            fn () => app(PopularCollectionsService::class)->cards()
        ));
        $designBanner = AppUrl::rewriteCachedValue($homepageService->resolveDesignBanner());
        $catalogShowcaseService = app(CatalogShowcaseService::class);
        $catalogShowcase = AppUrl::rewriteCachedValue(Cache::remember(
            'shop.catalog_showcase',
            3600,
            fn () => $catalogShowcaseService->resolve()
        ));
        $catalogShowcaseFurniture = AppUrl::rewriteCachedValue(Cache::remember(
            'shop.catalog_showcase_furniture',
            3600,
            fn () => $catalogShowcaseService->resolve(
                'homepage_catalog_showcase_furniture',
                config('homepage.catalog_showcase_furniture', [])
            )
        ));
        $promoBanner = AppUrl::rewriteCachedValue($homepageService->resolvePromoBanner());
        $customerReviewsService = app(CustomerReviewsService::class);
        $customerReviewCards = AppUrl::normalizeReviewCards(Cache::remember(
            'shop.customer_reviews',
            3600,
            fn () => $customerReviewsService->cards()
        ));
        $customerReviewsTitle = $customerReviewsService->sectionTitle();
        $contactStrip = $homepageService->resolveContactStrip();
        $comfortSpotlight = AppUrl::normalizeComfortSpotlight(Cache::remember(
            'shop.comfort_spotlight',
            3600,
            fn () => app(ComfortSpotlightService::class)->resolve()
        ));
        $homeCategories = $homepageService->categoriesForHome();
        $navCategories = app(CategoryBrowseService::class)->categoriesForNav();

        return view('shop.home', compact(
            'featured',
            'categoryTree',
            'flashProducts',
            'flashSales',
            'bundles',
            'seo',
            'homepage',
            'popularCollectionCards',
            'designBanner',
            'catalogShowcase',
            'catalogShowcaseFurniture',
            'promoBanner',
            'customerReviewCards',
            'customerReviewsTitle',
            'contactStrip',
            'comfortSpotlight',
            'homeCategories',
            'navCategories',
        ));
    }
}
