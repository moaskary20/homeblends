<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Models\Category;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminProductCreateRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_product_form_includes_relation_sections(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Livewire::test(CreateProduct::class)
            ->assertFormFieldExists('gallery_images')
            ->assertFormFieldExists('product_variants')
            ->assertFormFieldExists('flash_sale_entries')
            ->assertFormFieldExists('related_product_ids');
    }

    public function test_creating_product_persists_variants_flash_and_related(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create(['name' => 'أثاث', 'slug' => 'furniture-create', 'is_active' => true]);
        $related = Product::factory()->create(['name' => 'منتج مرتبط', 'sku' => 'REL-1']);
        $flashSale = FlashSale::create([
            'name' => 'فلاش تجريبي',
            'slug' => 'flash-create-test',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'name' => 'منتج جديد بالعلاقات',
                'slug' => 'product-with-relations',
                'sku' => 'NEW-REL-1',
                'status' => ProductStatus::Published->value,
                'regular_price' => 1000,
                'stock_quantity' => 10,
                'gallery_images' => [
                    [
                        'path' => [UploadedFile::fake()->image('demo-a.jpg')],
                        'alt' => 'صورة أ',
                        'sort_order' => 1,
                    ],
                ],
                'product_variants' => [
                    [
                        'sku' => 'NEW-REL-1-RED',
                        'barcode' => null,
                        'price' => 950,
                        'compare_price' => 1100,
                        'stock_quantity' => 4,
                        'image' => null,
                        'is_default' => true,
                    ],
                ],
                'flash_sale_entries' => [
                    [
                        'flash_sale_id' => $flashSale->id,
                        'sale_price' => 800,
                        'stock_limit' => 3,
                        'sort_order' => 0,
                    ],
                ],
                'related_product_ids' => [$related->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->where('sku', 'NEW-REL-1')->first();
        $this->assertNotNull($product);

        $this->assertGreaterThanOrEqual(1, ProductImage::query()->where('product_id', $product->id)->count());
        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'alt' => 'صورة أ',
        ]);

        $variant = ProductVariant::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($variant);
        $this->assertSame('NEW-REL-1-RED', $variant->sku);
        $this->assertTrue((bool) $variant->is_default);
        $this->assertEquals(950.0, (float) $variant->price);

        $this->assertTrue($product->relatedProducts()->where('products.id', $related->id)->exists());

        $this->assertDatabaseHas('flash_sale_products', [
            'flash_sale_id' => $flashSale->id,
            'product_id' => $product->id,
            'sale_price' => 800,
            'stock_limit' => 3,
        ]);
        $this->assertSame(1, FlashSaleProduct::query()->where('product_id', $product->id)->count());
    }
}
