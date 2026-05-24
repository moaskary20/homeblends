<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::where('slug', 'blenders')->first()
            ?? Category::first();

        if (! $category) {
            return;
        }

        Product::firstOrCreate(
            ['sku' => 'HB-BLEND-001'],
            [
                'category_id' => $category->id,
                'name' => 'خلاط هوم بلند برو 1200 واط',
                'slug' => 'homeblend-pro-1200w',
                'short_description' => 'خلاط احترافي لجميع استخدامات المطبخ.',
                'full_description' => '<p>خلاط عالي الأداء بعدة سرعات وشفرات من الستانلس ستيل.</p>',
                'regular_price' => 2499.00,
                'discount_price' => 1999.00,
                'stock_quantity' => 50,
                'status' => ProductStatus::Published,
                'is_featured' => true,
                'meta_title' => 'خلاط هوم بلند برو 1200 واط',
                'meta_description' => 'اشتري خلاط هوم بلند برو بأفضل سعر في مصر مع توصيل سريع.',
            ]
        );
    }
}
