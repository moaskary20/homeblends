<?php

namespace App\Services\Seo;

use App\Data\SeoMeta;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Services\Settings\SettingsService;
use App\Support\ProductMedia;
use Illuminate\Support\Str;

class SeoService
{
    public function __construct(protected SettingsService $settings) {}

    public function defaults(): SeoMeta
    {
        $siteName = $this->siteName();

        return new SeoMeta(
            title: $siteName,
            description: $this->defaultDescription(),
            canonical: url('/'),
            robots: $this->defaultRobots(),
            ogType: 'website',
            ogImage: $this->defaultOgImage(),
            ogUrl: url('/'),
            twitterCard: config('seo.twitter_card'),
            twitterSite: $this->settings->get('seo_twitter_site'),
            googleVerification: $this->settings->get('seo_google_verification'),
            schema: [$this->organizationSchema(), $this->websiteSchema()],
        );
    }

    public function forHome(): SeoMeta
    {
        $title = $this->settings->get('seo_home_title') ?: $this->siteName();
        $description = $this->settings->get('seo_home_description') ?: $this->defaultDescription();
        $canonical = route('shop.home');

        return new SeoMeta(
            title: $this->formatTitle($title),
            description: $description,
            canonical: $canonical,
            robots: $this->defaultRobots(),
            ogType: 'website',
            ogTitle: $title,
            ogDescription: $description,
            ogImage: $this->defaultOgImage(),
            ogUrl: $canonical,
            twitterCard: config('seo.twitter_card'),
            twitterSite: $this->settings->get('seo_twitter_site'),
            googleVerification: $this->settings->get('seo_google_verification'),
            schema: [
                $this->organizationSchema(),
                $this->websiteSchema(),
            ],
        );
    }

    public function forProductsIndex(?Category $category = null): SeoMeta
    {
        if ($category) {
            $title = $category->meta_title ?: $category->name;
            $description = $category->meta_description
                ?: Str::limit(strip_tags($category->description ?? ''), 160)
                ?: $this->defaultDescription();
            $canonical = route('shop.products.index', ['category_id' => $category->id]);

            return $this->pageMeta(
                title: $title,
                description: $description,
                canonical: $canonical,
                ogType: 'website',
                schema: [
                    $this->organizationSchema(),
                    $this->breadcrumbSchema([
                        ['name' => $this->siteName(), 'url' => route('shop.home')],
                        ['name' => __('Products'), 'url' => route('shop.products.index')],
                        ['name' => $category->name, 'url' => $canonical],
                    ]),
                ],
            );
        }

        return $this->pageMeta(
            title: __('Products'),
            description: $this->settings->get('seo_products_description') ?: __('Browse our full catalog of home appliances.'),
            canonical: route('shop.products.index'),
            schema: [
                $this->organizationSchema(),
                $this->breadcrumbSchema([
                    ['name' => $this->siteName(), 'url' => route('shop.home')],
                    ['name' => __('Products'), 'url' => route('shop.products.index')],
                ]),
            ],
        );
    }

    public function forProduct(Product $product): SeoMeta
    {
        $title = $product->meta_title ?: $product->name;
        $description = $product->meta_description
            ?: Str::limit(strip_tags($product->short_description ?? ''), 160);
        $canonical = route('shop.products.show', $product->slug);
        $image = ProductMedia::productThumbnail($product) ?? $this->defaultOgImage();

        $schemas = [
            $this->organizationSchema(),
            $this->breadcrumbSchema([
                ['name' => $this->siteName(), 'url' => route('shop.home')],
                ['name' => __('Products'), 'url' => route('shop.products.index')],
                ['name' => $product->name, 'url' => $canonical],
            ]),
            $this->productSchema($product, $canonical, $image),
        ];

        return $this->pageMeta(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogType: 'product',
            ogImage: $image,
            schema: $schemas,
        );
    }

    public function forBundlesIndex(): SeoMeta
    {
        return $this->pageMeta(
            title: __('ecommerce.product_bundles'),
            description: $this->settings->get('seo_bundles_description')
                ?: __('ecommerce.bundles_section'),
            canonical: route('shop.bundles.index'),
            schema: [
                $this->organizationSchema(),
                $this->breadcrumbSchema([
                    ['name' => $this->siteName(), 'url' => route('shop.home')],
                    ['name' => __('ecommerce.product_bundles'), 'url' => route('shop.bundles.index')],
                ]),
            ],
        );
    }

    public function forBundle(ProductBundle $bundle): SeoMeta
    {
        $title = $bundle->name;
        $description = Str::limit(strip_tags($bundle->short_description ?: $bundle->description ?: ''), 160)
            ?: $this->defaultDescription();
        $canonical = route('shop.bundles.show', $bundle->slug);
        $image = ProductMedia::url($bundle->main_image) ?? $this->defaultOgImage();

        return $this->pageMeta(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogType: 'product',
            ogImage: $image,
            schema: [
                $this->organizationSchema(),
                $this->breadcrumbSchema([
                    ['name' => $this->siteName(), 'url' => route('shop.home')],
                    ['name' => __('ecommerce.product_bundles'), 'url' => route('shop.bundles.index')],
                    ['name' => $bundle->name, 'url' => $canonical],
                ]),
                $this->bundleSchema($bundle, $canonical, $image),
            ],
        );
    }

    public function forPrivatePage(string $title): SeoMeta
    {
        return $this->pageMeta(
            title: $title,
            description: null,
            canonical: null,
            robots: config('seo.private_robots'),
            schema: [],
        );
    }

    public function forCart(): SeoMeta
    {
        return $this->pageMeta(
            title: __('ecommerce.cart'),
            description: __('ecommerce.cart_meta'),
            canonical: route('shop.cart'),
            robots: config('seo.private_robots'),
            schema: [
                $this->organizationSchema(),
                $this->breadcrumbSchema([
                    ['name' => $this->siteName(), 'url' => route('shop.home')],
                    ['name' => __('ecommerce.cart'), 'url' => route('shop.cart')],
                ]),
            ],
        );
    }

    public function forDepartmentsIndex(): SeoMeta
    {
        return $this->pageMeta(
            title: __('ecommerce.departments'),
            description: __('ecommerce.departments_meta'),
            canonical: route('shop.categories.index'),
            schema: [
                $this->organizationSchema(),
                $this->breadcrumbSchema([
                    ['name' => $this->siteName(), 'url' => route('shop.home')],
                    ['name' => __('ecommerce.departments'), 'url' => route('shop.categories.index')],
                ]),
            ],
        );
    }

    public function forAbout(string $title, ?string $description = null): SeoMeta
    {
        return $this->pageMeta(
            title: $title,
            description: $description ?: $this->defaultDescription(),
            canonical: route('shop.about'),
            schema: [
                $this->organizationSchema(),
                $this->breadcrumbSchema([
                    ['name' => $this->siteName(), 'url' => route('shop.home')],
                    ['name' => $title, 'url' => route('shop.about')],
                ]),
            ],
        );
    }

    public function forContact(string $title, ?string $description = null): SeoMeta
    {
        return $this->pageMeta(
            title: $title,
            description: $description ?: $this->defaultDescription(),
            canonical: route('shop.contact'),
            schema: [
                $this->organizationSchema(),
                $this->breadcrumbSchema([
                    ['name' => $this->siteName(), 'url' => route('shop.home')],
                    ['name' => $title, 'url' => route('shop.contact')],
                ]),
            ],
        );
    }

    public function forDesignTeam(string $title, ?string $description = null): SeoMeta
    {
        return $this->pageMeta(
            title: $title,
            description: $description ?: $this->defaultDescription(),
            canonical: route('shop.design-team'),
            schema: [
                $this->organizationSchema(),
                $this->breadcrumbSchema([
                    ['name' => $this->siteName(), 'url' => route('shop.home')],
                    ['name' => $title, 'url' => route('shop.design-team')],
                ]),
            ],
        );
    }

    public function forLegalPage(string $title, ?string $description, string $canonical): SeoMeta
    {
        return $this->pageMeta(
            title: $title,
            description: $description ?: $this->defaultDescription(),
            canonical: $canonical,
            schema: [
                $this->organizationSchema(),
                $this->breadcrumbSchema([
                    ['name' => $this->siteName(), 'url' => route('shop.home')],
                    ['name' => $title, 'url' => $canonical],
                ]),
            ],
        );
    }

    public function forCategoryBrowse(Category $category): SeoMeta
    {
        $title = $category->meta_title ?: $category->name;
        $description = $category->meta_description
            ?: Str::limit(strip_tags($category->description ?? ''), 160)
            ?: $this->defaultDescription();
        $canonical = route('shop.categories.show', $category->slug);

        return $this->pageMeta(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogType: 'website',
            schema: [
                $this->organizationSchema(),
                $this->breadcrumbSchema([
                    ['name' => $this->siteName(), 'url' => route('shop.home')],
                    ['name' => __('ecommerce.departments'), 'url' => route('shop.categories.index')],
                    ['name' => $category->name, 'url' => $canonical],
                ]),
            ],
        );
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?\Carbon\Carbon, priority: float, changefreq: string}>
     */
    public function sitemapEntries(): array
    {
        $entries = [
            [
                'loc' => route('shop.home'),
                'lastmod' => now(),
                'priority' => 1.0,
                'changefreq' => 'daily',
            ],
            [
                'loc' => route('shop.products.index'),
                'lastmod' => now(),
                'priority' => 0.9,
                'changefreq' => 'daily',
            ],
            [
                'loc' => route('shop.bundles.index'),
                'lastmod' => now(),
                'priority' => 0.8,
                'changefreq' => 'weekly',
            ],
            [
                'loc' => route('shop.categories.index'),
                'lastmod' => now(),
                'priority' => 0.85,
                'changefreq' => 'weekly',
            ],
            [
                'loc' => route('shop.about'),
                'lastmod' => now(),
                'priority' => 0.75,
                'changefreq' => 'monthly',
            ],
            [
                'loc' => route('shop.contact'),
                'lastmod' => now(),
                'priority' => 0.75,
                'changefreq' => 'monthly',
            ],
            [
                'loc' => route('shop.design-team'),
                'lastmod' => now(),
                'priority' => 0.75,
                'changefreq' => 'monthly',
            ],
            [
                'loc' => route('shop.legal.privacy'),
                'lastmod' => now(),
                'priority' => 0.5,
                'changefreq' => 'yearly',
            ],
            [
                'loc' => route('shop.legal.terms'),
                'lastmod' => now(),
                'priority' => 0.5,
                'changefreq' => 'yearly',
            ],
            [
                'loc' => route('shop.legal.returns'),
                'lastmod' => now(),
                'priority' => 0.5,
                'changefreq' => 'yearly',
            ],
            [
                'loc' => route('shop.legal.shipping'),
                'lastmod' => now(),
                'priority' => 0.5,
                'changefreq' => 'yearly',
            ],
        ];

        Product::published()->get(['slug', 'updated_at'])->each(function (Product $product) use (&$entries) {
            $entries[] = [
                'loc' => route('shop.products.show', $product->slug),
                'lastmod' => $product->updated_at,
                'priority' => 0.8,
                'changefreq' => 'weekly',
            ];
        });

        Category::active()->get(['slug', 'updated_at'])->each(function (Category $category) use (&$entries) {
            $entries[] = [
                'loc' => route('shop.categories.show', $category->slug),
                'lastmod' => $category->updated_at,
                'priority' => 0.7,
                'changefreq' => 'weekly',
            ];
        });

        ProductBundle::query()
            ->active()
            ->get(['slug', 'updated_at'])
            ->each(function (ProductBundle $bundle) use (&$entries) {
                $entries[] = [
                    'loc' => route('shop.bundles.show', $bundle->slug),
                    'lastmod' => $bundle->updated_at,
                    'priority' => 0.7,
                    'changefreq' => 'weekly',
                ];
            });

        return $entries;
    }

    protected function pageMeta(
        string $title,
        ?string $description,
        ?string $canonical,
        string $ogType = 'website',
        ?string $ogImage = null,
        ?string $robots = null,
        array $schema = [],
    ): SeoMeta {
        return new SeoMeta(
            title: $this->formatTitle($title),
            description: $description,
            canonical: $canonical,
            robots: $robots ?? $this->defaultRobots(),
            ogType: $ogType,
            ogTitle: $title,
            ogDescription: $description,
            ogImage: $ogImage ?? $this->defaultOgImage(),
            ogUrl: $canonical,
            twitterCard: config('seo.twitter_card'),
            twitterSite: $this->settings->get('seo_twitter_site'),
            googleVerification: $this->settings->get('seo_google_verification'),
            schema: $schema,
        );
    }

    protected function formatTitle(string $title): string
    {
        $suffix = $this->settings->get('seo_title_suffix', config('seo.title_separator').$this->siteName());

        if (Str::contains($title, $this->siteName())) {
            return $title;
        }

        return rtrim($title).$suffix;
    }

    protected function siteName(): string
    {
        return (string) ($this->settings->get('seo_site_name') ?: config('app.name'));
    }

    protected function defaultDescription(): string
    {
        return (string) ($this->settings->get('seo_default_description') ?: config('seo.default_description'));
    }

    protected function defaultRobots(): string
    {
        return (string) ($this->settings->get('seo_robots') ?: config('seo.default_robots'));
    }

    protected function defaultOgImage(): ?string
    {
        $path = $this->settings->get('seo_default_og_image');

        return $path ? \App\Support\AppUrl::normalize(\App\Support\AppUrl::absolute('storage/'.$path)) : null;
    }

    protected function organizationSchema(): array
    {
        $logo = $this->settings->get('seo_organization_logo');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->settings->get('seo_organization_name') ?: $this->siteName(),
            'url' => url('/'),
            'logo' => $logo ? \App\Support\AppUrl::normalize(\App\Support\AppUrl::absolute('storage/'.$logo)) : null,
        ]);
    }

    protected function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->siteName(),
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('shop.products.index', ['q' => '{search_term_string}']),
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     */
    protected function breadcrumbSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }

    protected function productSchema(Product $product, string $url, ?string $image): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => Str::limit(strip_tags($product->short_description ?? ''), 500),
            'sku' => $product->sku,
            'url' => $url,
            'image' => $image,
            'brand' => [
                '@type' => 'Brand',
                'name' => $this->siteName(),
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'price' => number_format($product->effective_price, 2, '.', ''),
                'priceCurrency' => 'EGP',
                'availability' => $product->stock_quantity > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ];

        if ($product->reviews_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $product->avg_rating,
                'reviewCount' => (string) $product->reviews_count,
            ];
        }

        return $schema;
    }

    protected function bundleSchema(ProductBundle $bundle, string $url, ?string $image): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $bundle->name,
            'description' => Str::limit(strip_tags($bundle->short_description ?: $bundle->description ?: ''), 500),
            'url' => $url,
            'image' => $image,
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'price' => number_format((float) $bundle->bundle_price, 2, '.', ''),
                'priceCurrency' => 'EGP',
                'availability' => $bundle->isAvailable()
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ];
    }
}
