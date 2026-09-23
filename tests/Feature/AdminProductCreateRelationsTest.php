<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\RelationManagers\FlashSalesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\RelatedProductsRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\VariantsRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminProductCreateRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_product_opens_edit_with_relation_tabs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Category::create(['name' => 'أثاث', 'slug' => 'furniture-create', 'is_active' => true]);

        $this->actingAs($admin);

        Livewire::test(CreateProduct::class)
            ->assertRedirect();

        $product = Product::query()->latest('id')->first();
        $this->assertNotNull($product);
        $this->assertSame(ProductStatus::Draft, $product->status);
        $this->assertStringStartsWith('DRAFT-', $product->sku);

        $this->assertSame(
            ProductResource::getUrl('edit', ['record' => $product]),
            url()->to(ProductResource::getUrl('edit', ['record' => $product]))
        );

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSuccessful()
            ->assertDontSee(__('ecommerce.product_details_tab'))
            ->assertSee(__('ecommerce.gallery'))
            ->assertSee(__('ecommerce.variants'))
            ->assertSee(__('ecommerce.flash_sales'))
            ->assertSee(__('ecommerce.related_products'));
    }

    public function test_edit_product_keeps_form_above_relation_tabs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::factory()->create();

        $this->actingAs($admin);

        $component = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()]);

        $this->assertFalse($component->instance()->hasCombinedRelationManagerTabsWithContent());
        $this->assertContains(ImagesRelationManager::class, ProductResource::getRelations());
        $this->assertContains(VariantsRelationManager::class, ProductResource::getRelations());
        $this->assertContains(FlashSalesRelationManager::class, ProductResource::getRelations());
        $this->assertContains(RelatedProductsRelationManager::class, ProductResource::getRelations());
    }
}
