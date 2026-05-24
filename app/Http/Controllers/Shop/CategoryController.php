<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoService;
use App\Services\Shop\CategoryBrowseService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(CategoryBrowseService $browse)
    {
        $categories = $browse->categoriesForHome();

        $seo = app(SeoService::class)->forDepartmentsIndex();

        return view('shop.categories.index', compact('categories', 'seo'));
    }

    public function show(string $slug, Request $request, CategoryBrowseService $browse)
    {
        $category = $browse->getCategoryBySlug($slug);
        abort_unless($category, 404);

        if ($browse->shouldShowSubcategoryLanding($category, $request->all())) {
            $seo = app(SeoService::class)->forCategoryBrowse($category);

            return view('shop.categories.subcategories', compact('category', 'seo'));
        }

        $filters = $browse->normalizeFilters($category, $request->all());
        $items = $browse->browseProducts([
            'category_ids' => $filters['category_ids'],
            'min_price' => $filters['min_price'],
            'max_price' => $filters['max_price'],
            'sort' => $filters['sort'],
            'in_stock' => $filters['in_stock'],
            'q' => $filters['q'],
            'attributes' => $filters['attributes'],
        ], 12);

        $facets = $browse->attributeFacetsForCategory($filters['category_ids']);
        $priceRange = $filters['price_range'];

        $seo = app(SeoService::class)->forCategoryBrowse($category);

        return view('shop.categories.show', compact(
            'category',
            'items',
            'facets',
            'priceRange',
            'filters',
            'seo'
        ));
    }
}
