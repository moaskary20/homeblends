<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupDepartmentSubcategoriesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_subcategories_with_images_under_main_departments(): void
    {
        foreach (config('categories.main_departments', []) as $department) {
            Category::create([
                'name' => $department['name'],
                'slug' => $department['slug'],
                'is_active' => true,
                'sort_order' => $department['sort_order'] ?? 0,
            ]);
        }

        $legacy = Category::create([
            'name' => 'غرف المعيشة',
            'slug' => 'ariika-living-room-1',
            'parent_id' => Category::query()->where('slug', 'athath')->value('id'),
            'is_active' => true,
        ]);

        $this->artisan('categories:setup-subcategories')->assertSuccessful();

        $anterehat = Category::query()->where('slug', 'anterehat')->first();

        $this->assertNotNull($anterehat);
        $this->assertSame('أنتريهات', $anterehat->name);
        $this->assertSame('images/categories/living-room.jpg', $anterehat->image);
        $this->assertSame(
            Category::query()->where('slug', 'athath')->value('id'),
            $anterehat->parent_id
        );

        $legacy->refresh();
        $this->assertSame($anterehat->id, $legacy->parent_id);

        $this->assertDatabaseHas('categories', [
            'slug' => 'salons',
            'name' => 'صالونات',
        ]);
        $this->assertDatabaseHas('categories', [
            'slug' => 'indoor-flooring',
            'name' => 'أرضيات داخليه',
        ]);
    }

    public function test_creates_nested_sanitary_subcategories(): void
    {
        Category::create(['name' => 'صحي', 'slug' => 'sanitary', 'is_active' => true, 'sort_order' => 6]);

        $legacy = Category::create([
            'name' => 'خلاطات بانيو',
            'slug' => 'khamato-bath-mixers',
            'parent_id' => Category::query()->where('slug', 'sanitary')->value('id'),
            'is_active' => true,
        ]);

        $this->artisan('categories:setup-subcategories')->assertSuccessful();

        $mixers = Category::query()->where('slug', 'mixers')->first();
        $bathroomMixers = Category::query()->where('slug', 'bathroom-mixers')->first();

        $this->assertNotNull($mixers);
        $this->assertNotNull($bathroomMixers);
        $this->assertSame('خلاطات', $mixers->name);
        $this->assertSame($mixers->id, $bathroomMixers->parent_id);
        $this->assertSame('images/categories/bathroom-mixers.jpg', $bathroomMixers->image);

        $legacy->refresh();
        $this->assertSame($bathroomMixers->id, $legacy->parent_id);
    }

    public function test_ceramics_keeps_only_four_flat_subcategories(): void
    {
        $ceramics = Category::create(['name' => 'سيراميك', 'slug' => 'ceramics', 'is_active' => true, 'sort_order' => 2]);
        $indoor = Category::create([
            'name' => 'أرضيات داخليه',
            'slug' => 'indoor-flooring',
            'parent_id' => $ceramics->id,
            'is_active' => true,
        ]);
        $legacy = Category::create([
            'name' => 'Gemma — سيراميك أرضيات',
            'slug' => 'gemma-floor-ceramic',
            'parent_id' => $indoor->id,
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $legacy->id,
            'name' => 'بلاط تجريبي',
            'slug' => 'sample-tile',
            'sku' => 'TILE-1',
            'regular_price' => 100,
            'stock_quantity' => 1,
            'status' => ProductStatus::Published,
        ]);

        $this->artisan('categories:setup-subcategories')->assertSuccessful();

        $this->assertNull(Category::query()->where('slug', 'gemma-floor-ceramic')->first());
        $this->assertSame($indoor->id, $product->fresh()->category_id);
        $this->assertCount(4, Category::query()->where('parent_id', $ceramics->id)->get());
        $this->assertSame(
            0,
            Category::query()
                ->where('parent_id', Category::query()->where('slug', 'indoor-flooring')->value('id'))
                ->count()
        );
    }

    public function test_root_category_shows_new_subcategories_landing(): void
    {
        $this->artisan('categories:setup-main')->assertSuccessful();
        $this->artisan('categories:setup-subcategories')->assertSuccessful();

        $response = $this->get(route('shop.categories.show', 'athath'));

        $response->assertOk();
        $response->assertSee('أنتريهات');
        $response->assertSee('غرف نوم ماستر');
        $response->assertSee(__('ecommerce.choose_subcategory'));
    }

    public function test_furniture_department_gets_new_subcategories_and_retires_old_ones(): void
    {
        $athath = Category::create(['name' => 'أثاث', 'slug' => 'athath', 'is_active' => true, 'sort_order' => 1]);
        $ceramics = Category::create(['name' => 'سيراميك', 'slug' => 'ceramics', 'is_active' => true, 'sort_order' => 2]);
        $ceramicsExtra = Category::create([
            'name' => 'Gemma — سيراميك أرضيات',
            'slug' => 'gemma-floor-ceramic',
            'parent_id' => $ceramics->id,
            'is_active' => true,
        ]);
        $oldBedrooms = Category::create([
            'name' => 'غرف نوم',
            'slug' => 'bedrooms',
            'parent_id' => $athath->id,
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $oldBedrooms->id,
            'name' => 'سرير قديم',
            'slug' => 'old-bed',
            'sku' => 'BED-OLD',
            'regular_price' => 100,
            'stock_quantity' => 1,
            'status' => ProductStatus::Published,
        ]);

        $this->artisan('categories:setup-subcategories', ['--department' => 'athath'])->assertSuccessful();

        $names = Category::query()
            ->where('parent_id', $athath->id)
            ->orderBy('sort_order')
            ->pluck('name')
            ->all();

        $this->assertSame([
            'أنتريهات', 'ركنات', 'كنب سرير', 'ريكلاينر', 'صالونات',
            'غرف نوم ماستر', 'غرف نوم شبابي و أطفالي', 'دولايب', 'سراير', 'مكاتب',
            'غرف سفرة', 'بوفيه', 'كراسى سفرة و سادات', 'ترابيزات - وسط', 'ترابيزات - جانبية',
            'وحدات تلفزيون', 'خزانات أحذية', 'أثاث حدائق', 'إضاءة', 'إكسسوارات',
        ], $names);

        $this->assertSoftDeleted('categories', ['id' => $oldBedrooms->id]);
        $this->assertSame(
            Category::query()->where('slug', 'master-bedrooms')->value('id'),
            $product->fresh()->category_id
        );

        $this->assertNotNull($ceramicsExtra->fresh());
        $this->assertNull(Category::query()->where('slug', 'mixers')->first());
    }

    public function test_configured_subcategories_show_when_empty(): void
    {
        $this->artisan('categories:setup-main')->assertSuccessful();
        $this->artisan('categories:setup-subcategories')->assertSuccessful();

        $response = $this->get(route('shop.categories.show', 'ceramics'));

        $response->assertOk();
        $response->assertSee('أرضيات داخليه');
        $response->assertSee('بورسلين');
        $response->assertSee(__('ecommerce.choose_subcategory'));
    }
}
