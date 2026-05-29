<?php

namespace Tests\Unit;

use App\Support\AppUrl;
use Tests\TestCase;

class AppUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://homeblendstore.com']);
    }

    public function test_normalize_rewrites_ip_host_to_app_url(): void
    {
        $url = 'https://38.242.251.149/media/360/images/customer-reviews/review-1.jpg';

        $this->assertSame(
            'https://homeblendstore.com/media/360/images/customer-reviews/review-1.jpg',
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
}
