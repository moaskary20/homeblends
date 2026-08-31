<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Product;
use App\Services\Offer\OfferProductSyncService;
use App\Services\Offer\OfferService;
use Illuminate\Database\Seeder;

class InstallmentOffersDemoSeeder extends Seeder
{
    public function run(): void
    {
        $offers = [
            [
                'name' => 'ركن الأطفال — تقسيط 6 أو 12 شهر',
                'slug' => 'kids-corner-6-months',
                'description' => 'طقم أثاث الأطفال من أريكا بسعر العرض، مع اختيار التقسيط على 6 أو 12 شهراً بدون فوائد.',
                'installment_months' => 6,
                'installment_plans' => [6, 12],
                'sort_order' => 1,
                'banner_from' => 477,
                'products' => [
                    477 => 1800,
                    472 => 1890,
                    473 => 2160,
                    468 => 2580,
                    475 => 2580,
                    476 => 2700,
                ],
            ],
            [
                'name' => 'تكييفات الصيف — تقسيط 12 أو 18 أو 24 شهر',
                'slug' => 'summer-ac-12-months',
                'description' => 'تكييفات 1.5 حصان من فريش وهاير وفري إير وجنرال إليكتريك وإل جي. اختر التقسيط على 12 أو 18 أو 24 شهراً بدون فوائد.',
                'installment_months' => 12,
                'installment_plans' => [12, 18, 24],
                'sort_order' => 2,
                'banner_from' => 537,
                'products' => [
                    537 => 21600,
                    538 => 22800,
                    539 => 24000,
                    540 => 24000,
                    541 => 25200,
                ],
            ],
            [
                'name' => 'أجهزة الغسيل والتبريد — تقسيط 12 أو 18 شهر',
                'slug' => 'laundry-cooling-18-months',
                'description' => 'غسالات وثلاجات مختارة بسعر العرض مع اختيار التقسيط على 12 أو 18 شهراً. متاح فقط داخل هذا العرض.',
                'installment_months' => 18,
                'installment_plans' => [12, 18],
                'sort_order' => 3,
                'banner_from' => 565,
                'products' => [
                    572 => 28800,
                    574 => 32400,
                    571 => 36000,
                    573 => 46800,
                    564 => 61200,
                    565 => 64800,
                ],
            ],
        ];

        $sync = app(OfferProductSyncService::class);

        foreach ($offers as $data) {
            $productIds = array_keys($data['products']);
            $existing = Product::query()->whereIn('id', $productIds)->pluck('id');

            if ($existing->isEmpty()) {
                continue;
            }

            $bannerProduct = Product::find($data['banner_from']);

            $offer = Offer::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'starts_at' => now()->subHour(),
                    'ends_at' => now()->addMonths(3),
                    'is_active' => true,
                    'installment_months' => $data['installment_months'],
                    'installment_plans' => $data['installment_plans'] ?? [$data['installment_months']],
                    'sort_order' => $data['sort_order'],
                    'banner_image' => $bannerProduct?->main_image,
                ]
            );

            $items = [];
            foreach ($data['products'] as $productId => $price) {
                if (! $existing->contains($productId)) {
                    continue;
                }

                $items[] = [
                    'product_id' => $productId,
                    'offer_price' => $price,
                    'sort_order' => count($items),
                ];
            }

            $sync->sync($offer, $items);
        }

        app(OfferService::class)->clearCaches();
    }
}
