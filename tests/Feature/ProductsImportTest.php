<?php

namespace Tests\Feature;

use App\Imports\ProductsImport;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ProductsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_products_from_array(): void
    {
        Excel::import(new ProductsImport, __DIR__.'/../fixtures/products-import-sample.csv');

        $this->assertDatabaseHas('products', ['sku' => 'TEST-IMPORT-001']);
        $this->assertDatabaseHas('categories', ['slug' => 'test-cat']);
    }

    public function test_updates_existing_product_by_sku(): void
    {
        $category = Category::create(['name' => 'تصنيف', 'slug' => 'cat', 'is_active' => true]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'قديم',
            'slug' => 'old',
            'sku' => 'TEST-IMPORT-001',
            'regular_price' => 10,
            'stock_quantity' => 1,
            'status' => 'published',
        ]);

        Excel::import(new ProductsImport, __DIR__.'/../fixtures/products-import-sample.csv');

        $product = Product::where('sku', 'TEST-IMPORT-001')->first();
        $this->assertSame('منتج تجريبي', $product->name);
    }
}
