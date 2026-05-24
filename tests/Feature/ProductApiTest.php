<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_published_products(): void
    {
        $category = Category::create([
            'name' => 'Test',
            'slug' => 'test',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TST-001',
            'regular_price' => 100,
            'stock_quantity' => 10,
            'status' => ProductStatus::Published,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Test Product');
    }

    public function test_featured_products_endpoint(): void
    {
        $response = $this->getJson('/api/v1/products/featured');

        $response->assertOk();
    }
}
