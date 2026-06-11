<?php

namespace Tests\Unit;

use App\Support\ProductMedia;
use Tests\TestCase;

class ProductMediaTest extends TestCase
{
    public function test_resize_url_serves_public_svg_without_media_pipeline(): void
    {
        $url = ProductMedia::resizeUrl('images/categories/sanitary.svg', 400);

        $this->assertNotNull($url);
        $this->assertStringEndsWith('/images/categories/sanitary.svg', $url);
        $this->assertStringNotContainsString('/media/', $url);
    }
}
