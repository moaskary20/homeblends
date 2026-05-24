<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Enums\ProductStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_lists_main_departments(): void
    {
        $furniture = Category::create(['name' => 'أثاث', 'slug' => 'athath', 'is_active' => true, 'sort_order' => 1]);
        Category::create(['name' => 'سيراميك', 'slug' => 'ceramics', 'is_active' => true, 'sort_order' => 2]);

        Product::create([
            'category_id' => $furniture->id,
            'name' => 'كنبة',
            'slug' => 'sofa',
            'sku' => 'SOFA-1',
            'regular_price' => 1000,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);

        $categories = app(\App\Services\Shop\CategoryBrowseService::class)->categoriesForHome();

        $this->assertTrue($categories->contains('slug', 'athath'));
        $this->assertTrue($categories->contains('slug', 'ceramics'));
    }

    public function test_category_page_filters_by_price(): void
    {
        $category = Category::create(['name' => 'قسم', 'slug' => 'cat-a', 'is_active' => true]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'رخيص',
            'slug' => 'cheap',
            'sku' => 'C-1',
            'regular_price' => 100,
            'stock_quantity' => 1,
            'status' => ProductStatus::Published,
        ]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'غالي',
            'slug' => 'expensive',
            'sku' => 'C-2',
            'regular_price' => 5000,
            'stock_quantity' => 1,
            'status' => ProductStatus::Published,
        ]);

        $response = $this->get(route('shop.categories.show', [
            'slug' => 'cat-a',
            'max_price' => 500,
        ]));

        $response->assertOk();
        $response->assertSee('رخيص');
        $response->assertDontSee('غالي');
    }

    public function test_root_category_shows_subcategories_landing(): void
    {
        $root = Category::create(['name' => 'سيراميك', 'slug' => 'ceramics', 'is_active' => true]);
        $child = Category::create([
            'name' => 'Gemma — مطبخ',
            'slug' => 'gemma-kitchen',
            'parent_id' => $root->id,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $child->id,
            'name' => 'بلاط مطبخ',
            'slug' => 'kitchen-tile',
            'sku' => 'KT-1',
            'regular_price' => 200,
            'stock_quantity' => 1,
            'status' => ProductStatus::Published,
        ]);

        $response = $this->get(route('shop.categories.show', 'ceramics'));

        $response->assertOk();
        $response->assertSee('Gemma — مطبخ');
        $response->assertSee(__('ecommerce.choose_subcategory'));
        $response->assertDontSee('بلاط مطبخ');
    }

    public function test_root_category_all_query_shows_products(): void
    {
        $root = Category::create(['name' => 'سيراميك', 'slug' => 'ceramics', 'is_active' => true]);
        $child = Category::create([
            'name' => 'Gemma — مطبخ',
            'slug' => 'gemma-kitchen',
            'parent_id' => $root->id,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $child->id,
            'name' => 'بلاط مطبخ',
            'slug' => 'kitchen-tile',
            'sku' => 'KT-1',
            'regular_price' => 200,
            'stock_quantity' => 1,
            'status' => ProductStatus::Published,
        ]);

        $response = $this->get(route('shop.categories.show', ['slug' => 'ceramics', 'all' => 1]));

        $response->assertOk();
        $response->assertSee('بلاط مطبخ');
    }

    public function test_category_page_filters_by_variant_attribute(): void
    {
        $category = Category::create(['name' => 'ألوان', 'slug' => 'colors', 'is_active' => true]);
        $group = AttributeGroup::create(['name' => 'خصائص', 'slug' => 'specs']);
        $attr = Attribute::create([
            'attribute_group_id' => $group->id,
            'name' => 'اللون',
            'slug' => 'color',
        ]);
        $red = AttributeValue::create(['attribute_id' => $attr->id, 'value' => 'أحمر', 'color_hex' => '#ff0000']);
        $blue = AttributeValue::create(['attribute_id' => $attr->id, 'value' => 'أزرق', 'color_hex' => '#0000ff']);

        $productRed = Product::create([
            'category_id' => $category->id,
            'name' => 'منتج أحمر',
            'slug' => 'red-item',
            'sku' => 'R-1',
            'regular_price' => 200,
            'stock_quantity' => 1,
            'status' => ProductStatus::Published,
        ]);
        $variantRed = ProductVariant::create([
            'product_id' => $productRed->id,
            'sku' => 'R-1-V',
            'price' => 200,
            'stock_quantity' => 1,
        ]);
        ProductVariantValue::create([
            'product_variant_id' => $variantRed->id,
            'attribute_id' => $attr->id,
            'attribute_value_id' => $red->id,
        ]);

        $productBlue = Product::create([
            'category_id' => $category->id,
            'name' => 'منتج أزرق',
            'slug' => 'blue-item',
            'sku' => 'B-1',
            'regular_price' => 300,
            'stock_quantity' => 1,
            'status' => ProductStatus::Published,
        ]);
        $variantBlue = ProductVariant::create([
            'product_id' => $productBlue->id,
            'sku' => 'B-1-V',
            'price' => 300,
            'stock_quantity' => 1,
        ]);
        ProductVariantValue::create([
            'product_variant_id' => $variantBlue->id,
            'attribute_id' => $attr->id,
            'attribute_value_id' => $blue->id,
        ]);

        $response = $this->get(route('shop.categories.show', [
            'slug' => 'colors',
            'attr' => [$attr->id => [$red->id]],
        ]));

        $response->assertOk();
        $response->assertSee('منتج أحمر');
        $response->assertDontSee('منتج أزرق');
    }
}
