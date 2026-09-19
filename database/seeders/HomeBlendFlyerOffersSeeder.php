<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use App\Services\Offer\OfferProductSyncService;
use App\Services\Offer\OfferService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomeBlendFlyerOffersSeeder extends Seeder
{
    private string $assetsRoot;

    public function run(): void
    {
        $this->assetsRoot = database_path('seeders/assets/homeblend-flyer');

        if (! is_dir($this->assetsRoot)) {
            $this->command?->error('Missing flyer assets at '.$this->assetsRoot);

            return;
        }

        $furniture = $this->categoryId(['athath', 'أثاث']);
        $ceramics = $this->categoryId(['ceramics', 'سيراميك']);
        $sanitary = $this->categoryId(['sanitary', 'صحي']);
        $textiles = $this->categoryId(['textiles', 'منسوجات']);

        $products = [
            'master-bedroom' => $this->upsertProduct([
                'slug' => 'hb-master-bedroom-set',
                'sku' => 'HB-PKG-MASTER',
                'name' => 'غرفة نوم رئيسية',
                'category_id' => $furniture,
                'short_description' => 'طقم غرفة نوم رئيسية كامل ضمن باقات Home Blend.',
                'regular_price' => 95000,
                'image' => 'master-bedroom.png',
            ]),
            'guest-bedroom' => $this->upsertProduct([
                'slug' => 'hb-guest-bedroom-set',
                'sku' => 'HB-PKG-GUEST',
                'name' => 'غرفة نوم ضيوف',
                'category_id' => $furniture,
                'short_description' => 'طقم غرفة نوم ضيوف أنيق ضمن باقات التشطيب.',
                'regular_price' => 70000,
                'image' => 'guest-bedroom.png',
            ]),
            'dining' => $this->upsertProduct([
                'slug' => 'hb-dining-room-set',
                'sku' => 'HB-PKG-DINING',
                'name' => 'غرفة سفرة',
                'category_id' => $furniture,
                'short_description' => 'طقم سفرة عصري لغرفة الطعام.',
                'regular_price' => 65000,
                'image' => 'dining.png',
            ]),
            'living' => $this->upsertProduct([
                'slug' => 'hb-living-room-set',
                'sku' => 'HB-PKG-LIVING',
                'name' => 'غرفة معيشة / رسيبشن',
                'category_id' => $furniture,
                'short_description' => 'طقم جلوس واستقبال لغرفة المعيشة.',
                'regular_price' => 85000,
                'image' => 'living.png',
            ]),
            'porcelain-reception-baths' => $this->upsertProduct([
                'slug' => 'hb-porcelain-reception-2baths',
                'sku' => 'HB-PKG-PORC-RB',
                'name' => 'بورسلين استقبال + حمامين',
                'category_id' => $ceramics,
                'short_description' => 'تشطيب بورسلين لرسيبشن الشقة وحمامين.',
                'regular_price' => 50000,
                'image' => 'porcelain.png',
            ]),
            'porcelain-reception' => $this->upsertProduct([
                'slug' => 'hb-porcelain-reception',
                'sku' => 'HB-PKG-PORC-R',
                'name' => 'بورسلين استقبال',
                'category_id' => $ceramics,
                'short_description' => 'بورسلين فاخر لرسيبشن الشقة.',
                'regular_price' => 45000,
                'image' => 'porcelain.png',
            ]),
            'porcelain-bath-kitchen' => $this->upsertProduct([
                'slug' => 'hb-porcelain-2baths-kitchen',
                'sku' => 'HB-PKG-PORC-BK',
                'name' => 'بورسلين حمامين + مطبخ',
                'category_id' => $ceramics,
                'short_description' => 'بورسلين لحمامين ومطبخ.',
                'regular_price' => 55000,
                'image' => 'porcelain.png',
            ]),
            'ceramic-wood-3rooms' => $this->upsertProduct([
                'slug' => 'hb-ceramic-wood-3rooms',
                'sku' => 'HB-PKG-CER-WOOD',
                'name' => 'سيراميك خشبي لـ 3 غرف',
                'category_id' => $ceramics,
                'short_description' => 'سيراميك مظهر خشب لثلاث غرف نوم.',
                'regular_price' => 40000,
                'image' => 'ceramic-wood.png',
            ]),
            'kitchen-ceramic' => $this->upsertProduct([
                'slug' => 'hb-kitchen-ceramic',
                'sku' => 'HB-PKG-KIT-CER',
                'name' => 'سيراميك مطبخ (أرضيات وحوائط)',
                'category_id' => $ceramics,
                'short_description' => 'سيراميك مطبخ للأرضيات والحوائط.',
                'regular_price' => 25000,
                'image' => 'kitchen-ceramic.png',
            ]),
            'hdf-3bedrooms' => $this->upsertProduct([
                'slug' => 'hb-hdf-3bedrooms',
                'sku' => 'HB-PKG-HDF',
                'name' => 'أرضيات HDF لـ 3 غرف نوم',
                'category_id' => $ceramics,
                'short_description' => 'باركيه HDF لثلاث غرف نوم.',
                'regular_price' => 50000,
                'image' => 'hdf.png',
            ]),
            'sanitary-2baths' => $this->upsertProduct([
                'slug' => 'hb-sanitary-2baths',
                'sku' => 'HB-PKG-SAN',
                'name' => 'طقم صحي لحمامين',
                'category_id' => $sanitary,
                'short_description' => 'أطقم صحية كاملة لحمامين.',
                'regular_price' => 45000,
                'image' => 'sanitary.png',
            ]),
            'mixers-showers-2baths' => $this->upsertProduct([
                'slug' => 'hb-mixers-showers-2baths',
                'sku' => 'HB-PKG-MIX',
                'name' => 'خلاطات + أعمدة دش لحمامين',
                'category_id' => $sanitary,
                'short_description' => 'طقم خلاطات وأعمدة دش لحمامين.',
                'regular_price' => 35000,
                'image' => 'mixers.png',
            ]),
            'kitchen-contishtal-eu' => $this->upsertProduct([
                'slug' => 'hb-kitchen-contishtal-european',
                'sku' => 'HB-PKG-KIT-EU',
                'name' => 'مطبخ خشب أوروبي — Contishtal',
                'category_id' => $furniture,
                'short_description' => 'مطبخ خشب أوروبي من شركة Contishtal.',
                'regular_price' => 200000,
                'image' => 'kitchen-contishtal.png',
            ]),
            'kitchen-contishtal-custom' => $this->upsertProduct([
                'slug' => 'hb-kitchen-contishtal-custom',
                'sku' => 'HB-PKG-KIT-CUS',
                'name' => 'مطبخ تفصيل — Contishtal',
                'category_id' => $furniture,
                'short_description' => 'مطبخ تفصيل من شركة Contishtal.',
                'regular_price' => 220000,
                'image' => 'kitchen-contishtal.png',
            ]),
            'curtains-sedar' => $this->upsertProduct([
                'slug' => 'hb-curtains-sedar-3',
                'sku' => 'HB-PKG-CUR-SEDAR',
                'name' => '3 ستائر استقبال وغرف نوم — Sedar',
                'category_id' => $textiles,
                'short_description' => 'ثلاث ستائر للاستقبال وغرفتين من شركة Sedar.',
                'regular_price' => 110000,
                'image' => 'curtains-sedar.png',
            ]),
        ];

        $offers = [
            [
                'slug' => 'offer-390',
                'name' => 'عرض 390',
                'description' => 'باقة تشطيب وأثاث متكاملة: غرف نوم رئيسية وضيوف وسفرة ومعيشة مع بورسلين وسيراميك وأطقم صحية. تقسيط 10 أشهر بدون فوائد — بدون استعلام أو ضمانات.',
                'banner' => 'offer-390.png',
                'down_payment_amount' => 130000,
                'sort_order' => 1,
                'items' => [
                    ['key' => 'master-bedroom', 'price' => 75000],
                    ['key' => 'guest-bedroom', 'price' => 55000],
                    ['key' => 'dining', 'price' => 50000],
                    ['key' => 'living', 'price' => 60000],
                    ['key' => 'porcelain-reception-baths', 'price' => 45000],
                    ['key' => 'ceramic-wood-3rooms', 'price' => 35000],
                    ['key' => 'kitchen-ceramic', 'price' => 20000],
                    ['key' => 'sanitary-2baths', 'price' => 30000],
                    ['key' => 'mixers-showers-2baths', 'price' => 20000],
                ],
            ],
            [
                'slug' => 'offer-490',
                'name' => 'عرض 490',
                'description' => 'باقة فاخرة مع بورسلين استقبال وحمامين ومطبخ وأرضيات HDF لغرف النوم. تقسيط 10 أشهر بدون فوائد.',
                'banner' => 'offer-490.png',
                'down_payment_amount' => 163300,
                'sort_order' => 2,
                'items' => [
                    ['key' => 'master-bedroom', 'price' => 85000],
                    ['key' => 'guest-bedroom', 'price' => 60000],
                    ['key' => 'dining', 'price' => 55000],
                    ['key' => 'living', 'price' => 70000],
                    ['key' => 'porcelain-reception', 'price' => 40000],
                    ['key' => 'porcelain-bath-kitchen', 'price' => 50000],
                    ['key' => 'hdf-3bedrooms', 'price' => 45000],
                    ['key' => 'sanitary-2baths', 'price' => 45000],
                    ['key' => 'mixers-showers-2baths', 'price' => 39300],
                ],
            ],
            [
                'slug' => 'offer-750',
                'name' => 'عرض 750',
                'description' => 'باقة شاملة مع مطبخ خشب أوروبي Contishtal و3 ستائر Sedar، بالإضافة للأثاث والتشطيبات. تقسيط 10 أشهر بدون فوائد.',
                'banner' => 'offer-750.png',
                'down_payment_amount' => 375000,
                'sort_order' => 3,
                'items' => [
                    ['key' => 'master-bedroom', 'price' => 90000],
                    ['key' => 'guest-bedroom', 'price' => 65000],
                    ['key' => 'dining', 'price' => 60000],
                    ['key' => 'living', 'price' => 80000],
                    ['key' => 'porcelain-reception-baths', 'price' => 50000],
                    ['key' => 'ceramic-wood-3rooms', 'price' => 40000],
                    ['key' => 'kitchen-ceramic', 'price' => 25000],
                    ['key' => 'sanitary-2baths', 'price' => 40000],
                    ['key' => 'mixers-showers-2baths', 'price' => 30000],
                    ['key' => 'kitchen-contishtal-eu', 'price' => 180000],
                    ['key' => 'curtains-sedar', 'price' => 90000],
                ],
            ],
            [
                'slug' => 'offer-850',
                'name' => 'عرض 850',
                'description' => 'أعلى باقة: أثاث وتشطيبات فاخرة مع مطبخ تفصيل Contishtal و3 ستائر Sedar وأرضيات HDF. تقسيط 10 أشهر بدون فوائد.',
                'banner' => 'offer-850.png',
                'down_payment_amount' => 425000,
                'sort_order' => 4,
                'items' => [
                    ['key' => 'master-bedroom', 'price' => 95000],
                    ['key' => 'guest-bedroom', 'price' => 70000],
                    ['key' => 'dining', 'price' => 65000],
                    ['key' => 'living', 'price' => 85000],
                    ['key' => 'porcelain-reception', 'price' => 45000],
                    ['key' => 'porcelain-bath-kitchen', 'price' => 55000],
                    ['key' => 'hdf-3bedrooms', 'price' => 50000],
                    ['key' => 'sanitary-2baths', 'price' => 45000],
                    ['key' => 'mixers-showers-2baths', 'price' => 35000],
                    ['key' => 'kitchen-contishtal-custom', 'price' => 200000],
                    ['key' => 'curtains-sedar', 'price' => 105000],
                ],
            ],
        ];

        $sync = app(OfferProductSyncService::class);

        foreach ($offers as $data) {
            $items = [];
            foreach ($data['items'] as $index => $row) {
                $product = $products[$row['key']] ?? null;
                if (! $product) {
                    continue;
                }

                $items[] = [
                    'product_id' => $product->id,
                    'offer_price' => $row['price'],
                    'stock_limit' => 20,
                    'sort_order' => $index,
                ];
            }

            $total = collect($items)->sum('offer_price');
            $down = (float) $data['down_payment_amount'];
            $monthly = round(($total - $down) / 10, 2);

            $offer = Offer::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => $data['description']."\n"
                        .'سعر العرض: '.number_format($total, 0).' ج.م — '
                        .'دفعة مقدمة: '.number_format($down, 0).' ج.م — '
                        .'قسط شهري: '.number_format($monthly, 0).' ج.م × 10 أشهر بدون فوائد.',
                    'starts_at' => now()->subHour(),
                    'ends_at' => now()->addYear(),
                    'is_active' => true,
                    'installment_months' => 10,
                    'installment_plans' => [10],
                    'down_payment_amount' => $down,
                    'sort_order' => $data['sort_order'],
                    'banner_image' => $this->storeOfferImage($data['banner']),
                    'gallery' => array_values(array_filter([
                        $this->storeOfferImage($data['banner']),
                        $this->storeOfferImage('flyer-source.jpg'),
                    ])),
                ]
            );

            $sync->sync($offer, $items);

            $this->command?->info(sprintf(
                '%s | total=%s | down=%s | monthly=%s | products=%d',
                $offer->name,
                number_format($total, 0),
                number_format($down, 0),
                number_format($monthly, 0),
                count($items)
            ));
        }

        app(OfferService::class)->clearCaches();
    }

    /**
     * @param  array{slug: string, sku: string, name: string, category_id: int, short_description: string, regular_price: float|int, image: string}  $data
     */
    protected function upsertProduct(array $data): Product
    {
        $imagePath = $this->storeProductImage($data['image']);

        return Product::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'sku' => $data['sku'],
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'short_description' => $data['short_description'],
                'full_description' => $data['short_description'].' — جزء من باقات Home Blend للتقسيط بدون فوائد.',
                'regular_price' => $data['regular_price'],
                'stock_quantity' => 50,
                'low_stock_threshold' => 5,
                'status' => ProductStatus::Published,
                'is_featured' => true,
                'main_image' => $imagePath,
            ]
        );
    }

    /**
     * @param  list<string>  $candidates
     */
    protected function categoryId(array $candidates): int
    {
        foreach ($candidates as $candidate) {
            $category = Category::query()
                ->where(function ($query) use ($candidate) {
                    $query->where('slug', $candidate)->orWhere('name', $candidate);
                })
                ->first();

            if ($category) {
                return (int) $category->id;
            }
        }

        $name = $candidates[1] ?? $candidates[0];
        $slug = Str::slug($candidates[0]);

        return (int) Category::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'is_active' => true,
                'sort_order' => 99,
            ]
        )->id;
    }

    protected function storeProductImage(string $filename): ?string
    {
        return $this->storeAsset('products/'.$filename, 'products/hb-flyer');
    }

    protected function storeOfferImage(string $filename): ?string
    {
        return $this->storeAsset('offers/'.$filename, 'offers/hb-flyer');
    }

    protected function storeAsset(string $relativeSource, string $destinationDir): ?string
    {
        $source = $this->assetsRoot.'/'.$relativeSource;
        if (! is_file($source)) {
            return null;
        }

        $extension = pathinfo($source, PATHINFO_EXTENSION) ?: 'png';
        $basename = pathinfo($source, PATHINFO_FILENAME);
        $target = trim($destinationDir, '/').'/'.$basename.'.'.$extension;

        Storage::disk('public')->makeDirectory($destinationDir);
        File::copy($source, Storage::disk('public')->path($target));

        return $target;
    }
}
