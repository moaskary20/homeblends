<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            VipLevelSeeder::class,
            TaxShippingSeeder::class,
            PaymentGatewaySeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
