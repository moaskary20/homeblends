<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * One-shot seeder for external/production servers after git pull + migrate.
 */
class ProductionDeploySeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TaxShippingSeeder::class,
            PaymentGatewaySeeder::class,
            MainCategoriesSeeder::class,
            HomepageContentSeeder::class,
            CatalogProductsSeeder::class,
            MainCategoriesSeeder::class,
        ]);

        app(\App\Services\Payment\PaymentGatewayService::class)->clearCache();

        $this->command?->info('Production deploy seed completed.');
    }
}
