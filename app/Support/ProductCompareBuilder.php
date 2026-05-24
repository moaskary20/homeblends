<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductCompareBuilder
{
    /**
     * @param  Collection<int, Product>  $products
     * @return array{products: Collection<int, Product>, rows: list<array{key: string, label: string, cells: list<array{product_id: int, html: string, highlight: bool}>}>}
     */
    public function build(Collection $products): array
    {
        $products = $products->values();

        if ($products->isEmpty()) {
            return ['products' => $products, 'rows' => []];
        }

        $rows = [
            $this->row('price', __('ecommerce.price'), $products, fn (Product $p) => $this->formatMoney($p->effective_price)),
            $this->row('regular_price', __('ecommerce.regular_price'), $products, fn (Product $p) => $this->formatMoney((float) $p->regular_price)),
            $this->row('category', __('ecommerce.category'), $products, fn (Product $p) => $p->category?->name ?? '—'),
            $this->row('availability', __('ecommerce.availability'), $products, function (Product $p) {
                if ($p->stock_quantity > 0) {
                    return __('ecommerce.product_in_stock');
                }

                return __('ecommerce.out_of_stock');
            }),
            $this->row('stock', __('ecommerce.stock_quantity'), $products, fn (Product $p) => $p->stock_quantity > 0
                ? (string) $p->stock_quantity
                : '0'),
            $this->row('sku', __('ecommerce.sku'), $products, fn (Product $p) => $p->sku ?: '—'),
            $this->row('weight', __('ecommerce.weight'), $products, fn (Product $p) => $p->weight
                ? number_format((float) $p->weight, 2).' '.__('ecommerce.kg')
                : '—'),
            $this->row('dimensions', __('ecommerce.dimensions'), $products, fn (Product $p) => $p->dimensions ?: '—'),
            $this->row('rating', __('ecommerce.rating'), $products, function (Product $p) {
                if ((float) $p->avg_rating <= 0) {
                    return '—';
                }

                return number_format((float) $p->avg_rating, 1).' ('.$p->reviews_count.')';
            }),
            $this->row('description', __('ecommerce.short_description'), $products, fn (Product $p) => $p->short_description
                ? Str::limit(strip_tags($p->short_description), 120)
                : '—'),
        ];

        return [
            'products' => $products,
            'rows' => array_values(array_filter($rows, fn (array $row) => collect($row['cells'])->contains(
                fn (array $cell) => $cell['html'] !== '—' && $cell['html'] !== ''
            ) || in_array($row['key'], ['price', 'regular_price', 'category', 'availability', 'stock', 'sku'], true))),
        ];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array{key: string, label: string, cells: list<array{product_id: int, html: string, highlight: bool}>}
     */
    private function row(string $key, string $label, Collection $products, callable $resolver): array
    {
        $rawValues = $products->map(fn (Product $p) => (string) $resolver($p))->values()->all();
        $highlight = count(array_unique($rawValues)) > 1;

        $cells = $products->map(fn (Product $p) => [
            'product_id' => $p->id,
            'html' => e((string) $resolver($p)),
            'highlight' => $highlight,
        ])->values()->all();

        return [
            'key' => $key,
            'label' => $label,
            'cells' => $cells,
        ];
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2).' '.__('ecommerce.currency');
    }
}
