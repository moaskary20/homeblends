<?php

namespace App\Imports;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class ProductsImport implements OnEachRow, SkipsEmptyRows, WithHeadingRow, WithValidation
{
    protected int $created = 0;

    protected int $updated = 0;

    /** @var Collection<int, string> */
    protected Collection $errors;

    public function __construct()
    {
        $this->errors = collect();
    }

    public function onRow(Row $row): void
    {
        $data = $this->normalizeRow($row->toArray());

        if (blank($data['sku'] ?? null)) {
            return;
        }

        try {
            $category = $this->resolveCategory($data);
            $payload = $this->mapProductPayload($data, $category->id);
            $product = Product::withTrashed()->where('sku', $payload['sku'])->first();

            if ($product) {
                if ($product->trashed()) {
                    $product->restore();
                }
                if ($payload['slug'] === null) {
                    unset($payload['slug']);
                }
                $product->update($payload);
                $this->updated++;
            } else {
                $payload['slug'] ??= Product::generateUniqueSlug($payload['name']);
                Product::create($payload);
                $this->created++;
            }
        } catch (\Throwable $e) {
            $this->errors->push(__('ecommerce.import_row_error', [
                'row' => $row->getIndex(),
                'message' => $e->getMessage(),
            ]));
        }
    }

    public function rules(): array
    {
        return [
            '*.sku' => ['required', 'string', 'max:100'],
            '*.name' => ['required', 'string', 'max:255'],
            '*.regular_price' => ['required', 'numeric', 'min:0'],
            '*.stock_quantity' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    public function getErrors(): Collection
    {
        return $this->errors;
    }

    protected function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[Str::slug(str_replace('_', '-', (string) $key), '_')] = is_string($value)
                ? trim($value)
                : $value;
        }

        return $normalized;
    }

    protected function resolveCategory(array $data): Category
    {
        $slug = $data['category_slug'] ?? null;
        $name = $data['category'] ?? $data['category_name'] ?? 'عام';

        if ($slug) {
            return Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true]
            );
        }

        $categorySlug = Category::slugify($name);

        return Category::firstOrCreate(
            ['slug' => $categorySlug],
            ['name' => $name, 'is_active' => true]
        );
    }

    protected function mapProductPayload(array $data, int $categoryId): array
    {
        $name = $data['name'];
        $slug = filled($data['slug'] ?? null) ? Product::slugify($data['slug']) : null;

        return [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'sku' => $data['sku'],
            'barcode' => $data['barcode'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'full_description' => $data['full_description'] ?? null,
            'main_image' => $data['main_image'] ?? null,
            'regular_price' => (float) ($data['regular_price'] ?? 0),
            'discount_price' => $this->nullableFloat($data['discount_price'] ?? null),
            'cost_price' => $this->nullableFloat($data['cost_price'] ?? null),
            'stock_quantity' => (int) ($data['stock_quantity'] ?? 0),
            'low_stock_threshold' => (int) ($data['low_stock_threshold'] ?? 5),
            'weight' => $this->nullableFloat($data['weight'] ?? null),
            'dimensions' => $data['dimensions'] ?? null,
            'status' => $this->parseStatus($data['status'] ?? 'published'),
            'is_featured' => $this->parseBool($data['is_featured'] ?? false),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ];
    }

    protected function parseStatus(mixed $value): ProductStatus
    {
        $status = Str::lower(trim((string) $value));

        return match ($status) {
            'draft', 'مسودة' => ProductStatus::Draft,
            'archived', 'مؤرشف' => ProductStatus::Archived,
            default => ProductStatus::Published,
        };
    }

    protected function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $v = Str::lower(trim((string) $value));

        return in_array($v, ['1', 'true', 'yes', 'نعم', 'y'], true);
    }

    protected function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
