<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Enums\ProductStatus;
use App\Filament\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    public function mount(): void
    {
        abort_unless(static::getResource()::canCreate(), 403);

        $categoryId = Category::query()->orderBy('id')->value('id');

        if (! $categoryId) {
            $categoryId = Category::query()->create([
                'name' => 'عام',
                'slug' => 'general-'.Str::lower(Str::random(4)),
                'is_active' => true,
            ])->id;
        }

        $suffix = strtoupper(Str::random(6));

        $product = Product::query()->create([
            'category_id' => $categoryId,
            'name' => __('ecommerce.new_product_draft_name'),
            'slug' => 'draft-'.Str::lower($suffix),
            'sku' => 'DRAFT-'.$suffix,
            'regular_price' => 0,
            'stock_quantity' => 0,
            'status' => ProductStatus::Draft,
            'is_featured' => false,
        ]);

        $this->redirect(ProductResource::getUrl('edit', ['record' => $product]).'?new=1');
    }
}
