<?php

namespace Tests\Unit;

use App\Support\AppUrl;
use Illuminate\Http\Request;
use Tests\TestCase;

class AppUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://homeblendstore.com']);
        config(['app.public_url' => null]);
    }

    public function test_normalize_rewrites_ip_host_to_app_url(): void
    {
        $url = 'https://38.242.251.149/media/360/images/customer-reviews/review-1.jpg';

        $this->assertSame(
            'https://homeblendstore.com/media/360/images/customer-reviews/review-1.jpg',
            AppUrl::normalize($url)
        );
    }

    public function test_normalize_rewrites_localhost_to_app_url(): void
    {
        $url = 'http://localhost/storage/products/chair.jpg';

        $this->assertSame(
            'https://homeblendstore.com/storage/products/chair.jpg',
            AppUrl::normalize($url)
        );
    }

    public function test_absolute_builds_from_relative_media_path(): void
    {
        $this->assertSame(
            'https://homeblendstore.com/media/144/products/foo.jpg',
            AppUrl::normalize('/media/144/products/foo.jpg')
        );
    }

    public function test_normalize_review_cards(): void
    {
        $cards = AppUrl::normalizeReviewCards(collect([
            ['image' => 'https://38.242.251.149/media/360/x.jpg', 'comment' => 'ok'],
        ]));

        $this->assertSame(
            'https://homeblendstore.com/media/360/x.jpg',
            $cards->first()['image']
        );
    }

    public function test_normalize_comfort_spotlight(): void
    {
        $spotlight = AppUrl::normalizeComfortSpotlight([
            'image_url' => 'https://38.242.251.149/media/960/hero.jpg',
            'thumbs' => [
                ['image' => 'https://38.242.251.149/media/144/thumb.jpg', 'url' => '/p', 'name' => 'A'],
            ],
        ]);

        $this->assertSame('https://homeblendstore.com/media/960/hero.jpg', $spotlight['image_url']);
        $this->assertSame('https://homeblendstore.com/media/144/thumb.jpg', $spotlight['thumbs'][0]['image']);
    }

    public function test_root_prefers_public_app_url_over_localhost_app_url(): void
    {
        config(['app.url' => 'http://localhost']);
        config(['app.public_url' => 'https://homeblendstore.com']);

        $this->assertSame('https://homeblendstore.com', AppUrl::root());
    }

    public function test_root_uses_request_host_when_app_url_is_localhost(): void
    {
        config(['app.url' => 'http://localhost']);
        config(['app.public_url' => null]);

        $this->app->instance('request', Request::create('https://homeblendstore.com/ar'));

        $this->assertSame('https://homeblendstore.com', AppUrl::root());
    }

    public function test_root_uses_request_host_for_local_api_server(): void
    {
        config(['app.url' => 'http://localhost']);
        config(['app.public_url' => null]);

        $this->app->instance('request', Request::create('http://127.0.0.1:8000/api/v1/categories'));

        $this->assertSame('http://127.0.0.1:8000', AppUrl::root());
        $this->assertSame(
            'http://127.0.0.1:8000/images/categories/athath.jpg',
            AppUrl::absolute('images/categories/athath.jpg')
        );
    }

    public function test_rewrite_cached_value_fixes_nested_localhost_urls(): void
    {
        $payload = [
            'items' => [
                ['image' => 'http://localhost/media/144/a.jpg'],
            ],
        ];

        $rewritten = AppUrl::rewriteCachedValue($payload);

        $this->assertSame(
            'https://homeblendstore.com/media/144/a.jpg',
            $rewritten['items'][0]['image']
        );
    }

    public function test_external_cdn_urls_are_not_rewritten(): void
    {
        $url = 'https://cdn.shopify.com/s/files/1/example.jpg';

        $this->assertSame($url, AppUrl::normalize($url));
    }
}
