<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\FlashSaleProduct;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** @var array<int, array<string, mixed>> */
    protected array $galleryImages = [];

    /** @var array<int, array<string, mixed>> */
    protected array $productVariants = [];

    /** @var array<int, int|string> */
    protected array $relatedProductIds = [];

    /** @var array<int, array<string, mixed>> */
    protected array $flashSaleEntries = [];

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->submit(null)
            ->requiresConfirmation()
            ->modalHeading(__('ecommerce.confirm_create_product_heading'))
            ->modalDescription(__('ecommerce.confirm_create_product_description'))
            ->modalSubmitActionLabel(__('ecommerce.confirm_create_product_submit'))
            ->action('create');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->requiresConfirmation()
            ->modalHeading(__('ecommerce.confirm_create_another_product_heading'))
            ->modalDescription(__('ecommerce.confirm_create_another_product_description'))
            ->modalSubmitActionLabel(__('ecommerce.confirm_create_another_product_submit'));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->galleryImages = array_values($data['gallery_images'] ?? []);
        $this->productVariants = array_values($data['product_variants'] ?? []);
        $this->relatedProductIds = array_values(array_filter($data['related_product_ids'] ?? []));
        $this->flashSaleEntries = array_values($data['flash_sale_entries'] ?? []);

        unset(
            $data['gallery_images'],
            $data['product_variants'],
            $data['related_product_ids'],
            $data['flash_sale_entries'],
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $product = $this->record;

        DB::transaction(function () use ($product): void {
            foreach ($this->galleryImages as $index => $row) {
                $path = $row['path'] ?? null;
                if (is_array($path)) {
                    $path = $path[0] ?? null;
                }
                if (! filled($path)) {
                    continue;
                }

                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'alt' => $row['alt'] ?? null,
                    'sort_order' => (int) ($row['sort_order'] ?? $index),
                ]);
            }

            $hasDefault = false;
            foreach ($this->productVariants as $row) {
                if (! filled($row['sku'] ?? null)) {
                    continue;
                }

                $isDefault = (bool) ($row['is_default'] ?? false);
                if ($isDefault) {
                    $hasDefault = true;
                }

                $image = $row['image'] ?? null;
                if (is_array($image)) {
                    $image = $image[0] ?? null;
                }

                ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'sku' => $row['sku'],
                    'barcode' => $row['barcode'] ?? null,
                    'price' => (float) ($row['price'] ?? 0),
                    'compare_price' => filled($row['compare_price'] ?? null) ? (float) $row['compare_price'] : null,
                    'stock_quantity' => (int) ($row['stock_quantity'] ?? 0),
                    'image' => $image,
                    'is_default' => $isDefault,
                ]);
            }

            if (! $hasDefault) {
                $firstVariant = $product->variants()->orderBy('id')->first();
                $firstVariant?->update(['is_default' => true]);
            }

            if ($this->relatedProductIds !== []) {
                $ids = collect($this->relatedProductIds)
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id) => $id > 0 && $id !== (int) $product->id)
                    ->unique()
                    ->values()
                    ->all();

                $product->relatedProducts()->sync($ids);
            }

            foreach ($this->flashSaleEntries as $index => $row) {
                $flashSaleId = (int) ($row['flash_sale_id'] ?? 0);
                if ($flashSaleId < 1 || ! filled($row['sale_price'] ?? null)) {
                    continue;
                }

                FlashSaleProduct::query()->updateOrCreate(
                    [
                        'flash_sale_id' => $flashSaleId,
                        'product_id' => $product->id,
                        'product_variant_id' => null,
                    ],
                    [
                        'sale_price' => (float) $row['sale_price'],
                        'stock_limit' => filled($row['stock_limit'] ?? null) ? (int) $row['stock_limit'] : null,
                        'sort_order' => (int) ($row['sort_order'] ?? $index),
                        'quantity_sold' => 0,
                    ],
                );
            }
        });
    }
}
