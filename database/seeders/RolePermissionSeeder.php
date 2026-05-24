<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'products.view', 'products.create', 'products.update', 'products.delete', 'products.import',
            'orders.view', 'orders.update', 'orders.manage',
            'categories.manage', 'coupons.manage', 'flash_sales.manage', 'bundles.manage', 'payment_gateways.manage', 'seo.manage', 'affiliates.manage', 'users.manage', 'roles.manage', 'loyalty.manage', 'shipping.manage', 'settings.manage', 'analytics.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => 'affiliate', 'guard_name' => 'web']);
    }
}
