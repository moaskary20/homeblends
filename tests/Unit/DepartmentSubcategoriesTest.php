<?php

namespace Tests\Unit;

use App\Services\ProductScraper\CleopatraScraperService;
use App\Services\ProductScraper\GemmaScraperService;
use App\Services\ProductScraper\KhamatoScraperService;
use App\Services\ProductScraper\MahgoubScraperService;
use App\Services\ProductScraper\SedarScraperService;
use App\Support\DepartmentSubcategories;
use App\Support\SanitarySubcategories;
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

    public function test_ceramics_scrapers_map_to_storefront_subcategories(): void
    {
        $this->assertSame('walls', DepartmentSubcategories::gemmaCeramicsSubcategorySlug('wall-ceramic'));
        $this->assertSame('porcelain', DepartmentSubcategories::gemmaCeramicsSubcategorySlug('glazed-porcelain-matt'));
        $this->assertSame('outdoor-flooring', DepartmentSubcategories::gemmaCeramicsSubcategorySlug('outdoor'));
        $this->assertSame('walls', DepartmentSubcategories::cleopatraCeramicsSubcategorySlug('wall'));
        $this->assertSame('indoor-flooring', DepartmentSubcategories::cleopatraCeramicsSubcategorySlug('marble-look'));
        $this->assertSame('porcelain', DepartmentSubcategories::mahgoubCeramicsSubcategorySlug('floor-porcelain'));
        $this->assertSame('walls', DepartmentSubcategories::mahgoubCeramicsSubcategorySlug('wall-ceramic'));
    }

    public function test_textiles_sedar_collections_map_to_storefront_subcategories(): void
    {
        $this->assertSame('pinch-pleat', DepartmentSubcategories::sedarSubcategorySlug('fabric-curtain-pinch-pleat'));
        $this->assertSame('ripple-fold', DepartmentSubcategories::sedarSubcategorySlug('fabric-curtain-ripple-fold'));
        $this->assertSame('blackout-curtains', DepartmentSubcategories::sedarSubcategorySlug('blackout-curtains'));
    }

    public function test_sanitary_scrapers_map_to_storefront_leaves(): void
    {
        $this->assertSame('kitchen-mixers', SanitarySubcategories::khamatoLeafSlug('basin-mixers'));
        $this->assertSame('basins', SanitarySubcategories::khamatoLeafSlug('bathroom-basins'));
        $this->assertSame('basins', SanitarySubcategories::mahgoubLeafSlug('sanitary-type-basin'));
        $this->assertSame('concealed-sanitary-sets', SanitarySubcategories::mahgoubLeafSlug('sanitary-concealed-tanks'));
    }

    public function test_scraper_admin_labels_use_menu_subcategory_names(): void
    {
        $ariika = app(\App\Services\ProductScraper\AriikaScraperService::class)->getFurnitureCollectionOptions();
        $this->assertStringStartsWith('ليفينج روم —', $ariika['living-room-1']);
        $this->assertStringStartsWith('غرف نوم —', $ariika['bedroom']);

        $gemma = app(GemmaScraperService::class)->getCollectionOptions();
        $this->assertStringStartsWith('حوائط —', $gemma['wall-ceramic']);
        $this->assertStringStartsWith('بورسلين —', $gemma['glazed-porcelain-matt']);

        $cleopatra = app(CleopatraScraperService::class)->getCollectionOptions();
        $this->assertStringStartsWith('أرضيات داخليه —', $cleopatra['marble-look']);

        $sedar = app(SedarScraperService::class)->getCollectionOptions();
        $this->assertStringStartsWith('ستائر بينش بليت —', $sedar['fabric-curtain-pinch-pleat']);

        $khamato = app(KhamatoScraperService::class)->getCollectionOptions();
        $this->assertStringStartsWith('خلاطات / مطابخ —', $khamato['basin-mixers']);
        $this->assertStringStartsWith('أجهزة صحية / أحواض —', $khamato['bathroom-basins']);

        $mahgoub = app(MahgoubScraperService::class)->getCollectionOptions();
        $this->assertStringStartsWith('بورسلين —', $mahgoub['floor-porcelain']);
        $this->assertStringStartsWith('أجهزة صحية / أحواض —', $mahgoub['sanitary-type-basin']);
    }
}
