<?php

namespace App\Services\Cart;

use App\Http\Resources\CartItemResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Offer;
use App\Support\ProductMedia;
use Illuminate\Support\Collection;

class CartDisplayLine
{
    /**
     * @param  array<int, array<string, mixed>>  $bundleItems
     */
    public function __construct(
        public readonly int $id,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly float $subtotal,
        public readonly bool $qtyLocked,
        public readonly bool $isBundle,
        public readonly bool $isOfferSet,
        public readonly string $title,
        public readonly ?string $imageUrl,
        public readonly string $url,
        public readonly ?string $meta,
        public readonly ?CartItem $item = null,
        public readonly ?Offer $offer = null,
        public readonly ?int $installmentMonths = null,
        public readonly int $productsCount = 0,
        public readonly array $bundleItems = [],
        public readonly ?string $variantSku = null,
    ) {}

    public static function fromCartItem(CartItem $item): self
    {
        $isBundle = $item->isBundleLine();
        $product = $item->product;
        $title = $isBundle
            ? ($item->bundle_snapshot['name'] ?? $item->bundle?->name ?? __('ecommerce.product_bundles'))
            : ($product?->name ?? '');
        $image = $product ? ProductMedia::productThumbnail($product) : null;
        $url = $product && ! $isBundle
            ? route('shop.products.show', $product->slug)
            : route('shop.bundles.index');

        return new self(
            id: $item->id,
            quantity: (int) $item->quantity,
            unitPrice: (float) $item->unit_price,
            subtotal: $item->subtotal,
            qtyLocked: false,
            isBundle: $isBundle,
            isOfferSet: false,
            title: $title,
            imageUrl: $image,
            url: $url,
            meta: null,
            item: $item,
            bundleItems: $isBundle ? array_values($item->bundle_snapshot['items'] ?? []) : [],
            variantSku: $item->variant?->sku,
        );
    }

    /**
     * @param  Collection<int, CartItem>  $items
     */
    public static function fromOfferSet(Offer $offer, Collection $items, Cart $cart): self
    {
        $first = $items->first();
        $subtotal = round($items->sum(fn (CartItem $item) => $item->subtotal), 2);
        $selected = (int) ($cart->installment_months ?? 0);
        $months = $offer->hasPlan($selected) ? $selected : $offer->defaultPlanMonths();
        $banner = ProductMedia::url($offer->banner_image);

        if (! $banner && $first?->product) {
            $banner = ProductMedia::productThumbnail($first->product);
        }

        $count = $items->count();
        $meta = trans_choice('ecommerce.offer_products_count', $count, ['count' => $count])
            .' · '
            .__('ecommerce.installment_months_badge', ['count' => $months]);

        return new self(
            id: (int) $first->id,
            quantity: 1,
            unitPrice: $subtotal,
            subtotal: $subtotal,
            qtyLocked: true,
            isBundle: false,
            isOfferSet: true,
            title: $offer->name,
            imageUrl: $banner,
            url: route('shop.offers.show', $offer->slug),
            meta: $meta,
            offer: $offer,
            installmentMonths: $months,
            productsCount: $count,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        if ($this->isOfferSet) {
            return [
                'id' => $this->id,
                'quantity' => 1,
                'qty_locked' => true,
                'unit_price' => $this->unitPrice,
                'subtotal' => $this->subtotal,
                'is_bundle' => false,
                'is_offer' => true,
                'is_offer_set' => true,
                'offer_product_id' => null,
                'offer' => [
                    'id' => $this->offer?->id,
                    'name' => $this->title,
                    'slug' => $this->offer?->slug,
                    'url' => $this->url,
                    'banner_image' => $this->imageUrl,
                    'installment_months' => $this->installmentMonths,
                    'products_count' => $this->productsCount,
                    'meta' => $this->meta,
                ],
                'bundle' => null,
                'product' => null,
            ];
        }

        $payload = (new CartItemResource($this->item))->resolve();
        $payload['qty_locked'] = false;
        $payload['is_offer_set'] = false;
        $payload['image'] = $this->imageUrl;
        $payload['url'] = $this->url;
        $payload['title'] = $this->title;

        return $payload;
    }
}
