<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Seo\SeoService;
use App\Services\Shop\CompareListService;
use App\Services\Shop\WishlistService;

class ProductController extends Controller
{
    public function index(ProductRepositoryInterface $products)
    {
        $items = $products->paginate(12, request()->only(['category_id', 'sort']));

        return view('shop.products.index', compact('items'));
    }

    public function show(string $slug, ProductRepositoryInterface $products)
    {
        $product = $products->findBySlug($slug, [
            'category', 'images', 'variants',
            'activeFlashSaleEntry.flashSale',
            'reviews' => fn ($q) => $q->approved(),
        ]);

        $flashPricing = $product
            ? app(\App\Services\FlashSale\FlashSaleService::class)->resolveUnitPrice($product)
            : null;

        abort_unless($product, 404);

        $seo = app(SeoService::class)->forProduct($product);

        $inWishlist = false;
        $inCompare = false;

        $sessionId = request()->session()->getId();
        $inWishlist = app(WishlistService::class)->has(auth()->user(), $sessionId, $product);
        $inCompare = app(CompareListService::class)->has(auth()->user(), $sessionId, $product);

        return view('shop.products.show', compact(
            'product',
            'flashPricing',
            'seo',
            'inWishlist',
            'inCompare',
        ));
    }
}
