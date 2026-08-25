<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminProductCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_products_can_be_filtered_by_category_including_children(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $parent = Category::create(['name' => 'أثاث', 'slug' => 'furniture', 'is_active' => true]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'غرف نوم',
            'slug' => 'bedrooms',
            'is_active' => true,
        ]);
        $other = Category::create(['name' => 'سيراميك', 'slug' => 'ceramic', 'is_active' => true]);

        $parentProduct = Product::create([
            'category_id' => $parent->id,
            'name' => 'منتج أب',
            'slug' => 'parent-product',
            'sku' => 'SKU-P',
            'regular_price' => 100,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);
        $childProduct = Product::create([
            'category_id' => $child->id,
            'name' => 'منتج فرعي',
            'slug' => 'child-product',
            'sku' => 'SKU-C',
            'regular_price' => 200,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);
        Product::create([
            'category_id' => $other->id,
            'name' => 'منتج آخر',
            'slug' => 'other-product',
            'sku' => 'SKU-O',
            'regular_price' => 50,
            'stock_quantity' => 5,
            'status' => ProductStatus::Published,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords([$parentProduct, $childProduct])
            ->filterTable('category_id', [$parent->id])
            ->assertCanSeeTableRecords([$parentProduct, $childProduct])
            ->assertCanNotSeeTableRecords(
                Product::query()->where('category_id', $other->id)->get()
            );
    }

    public function test_category_filter_options_show_parent_child_labels(): void
    {
        $parent = Category::create(['name' => 'أثاث', 'slug' => 'furniture-2', 'is_active' => true]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'غرف نوم',
            'slug' => 'bedrooms-2',
            'is_active' => true,
        ]);

        $method = new \ReflectionMethod(ProductResource::class, 'categoryFilterOptions');
        $method->setAccessible(true);
        $options = $method->invoke(null);

        $this->assertSame('أثاث', $options[$parent->id]);
        $this->assertSame('أثاث › غرف نوم', $options[$child->id]);
    }
}
