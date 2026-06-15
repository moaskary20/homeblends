<?php

namespace Tests\Unit;

use App\Support\ProductMedia;
use Tests\TestCase;

class ProductMediaTest extends TestCase
{
    public function test_resize_url_serves_public_assets_without_media_pipeline(): void
    {
        $svg = ProductMedia::resizeUrl('images/categories/sanitary.svg', 400);
        $jpg = ProductMedia::resizeUrl('images/categories/athath.jpg', 400);

        $this->assertNotNull($svg);
        $this->assertStringEndsWith('/images/categories/sanitary.svg', $svg);
        $this->assertStringNotContainsString('/media/', $svg);

        $this->assertNotNull($jpg);
        $this->assertStringEndsWith('/images/categories/athath.jpg', $jpg);
        $this->assertStringNotContainsString('/media/', $jpg);
    }
}
