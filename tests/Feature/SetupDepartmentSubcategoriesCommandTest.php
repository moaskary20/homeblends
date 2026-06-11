<?php

namespace Tests\Feature;

use App\Models\Category;
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

        $livingRoom = Category::query()->where('slug', 'living-room')->first();

        $this->assertNotNull($livingRoom);
        $this->assertSame('ليفينج روم', $livingRoom->name);
        $this->assertSame('images/categories/athath-living-room.svg', $livingRoom->image);
        $this->assertSame(
            Category::query()->where('slug', 'athath')->value('id'),
            $livingRoom->parent_id
        );

        $legacy->refresh();
        $this->assertSame($livingRoom->id, $legacy->parent_id);

        $this->assertDatabaseHas('categories', [
            'slug' => 'bathroom-accessories',
            'name' => 'اكسسورات حمام',
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
        $this->assertSame('images/categories/sanitary-bathroom-mixers.svg', $bathroomMixers->image);

        $legacy->refresh();
        $this->assertSame($bathroomMixers->id, $legacy->parent_id);
    }

    public function test_root_category_shows_new_subcategories_landing(): void
    {
        $this->artisan('categories:setup-main')->assertSuccessful();
        $this->artisan('categories:setup-subcategories')->assertSuccessful();

        $response = $this->get(route('shop.categories.show', 'accessories'));

        $response->assertOk();
        $response->assertSee('اكسسورات حمام');
        $response->assertSee('اكسسوارات الابواب');
        $response->assertSee(__('ecommerce.choose_subcategory'));
    }
}
