<?php

namespace Tests\Unit;

use App\Support\DepartmentSubcategories;
use Tests\TestCase;

class DepartmentSubcategoriesTest extends TestCase
{
    public function test_ariika_collections_map_to_storefront_subcategories(): void
    {
        $this->assertSame('living-room', DepartmentSubcategories::ariikaSubcategorySlug('living-room-1'));
        $this->assertSame('bedrooms', DepartmentSubcategories::ariikaSubcategorySlug('bedroom'));
        $this->assertSame('dining-rooms', DepartmentSubcategories::ariikaSubcategorySlug('dining-room'));
        $this->assertSame('outdoor', DepartmentSubcategories::ariikaSubcategorySlug('outdoor-1'));
        $this->assertSame('salons', DepartmentSubcategories::ariikaSubcategorySlug('indoor-sofas'));
    }

    public function test_ariika_admin_labels_use_menu_subcategory_names(): void
    {
        $options = app(\App\Services\ProductScraper\AriikaScraperService::class)
            ->getFurnitureCollectionOptions();

        $this->assertStringStartsWith('ليفينج روم —', $options['living-room-1']);
        $this->assertStringStartsWith('غرف نوم —', $options['bedroom']);
        $this->assertStringStartsWith('صلونات —', $options['indoor-sofas']);
    }
}
