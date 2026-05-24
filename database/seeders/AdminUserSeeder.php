<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@homeblend.store'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
                'locale' => 'ar',
                'currency' => 'EGP',
            ]
        );

        $admin->assignRole('admin');
    }
}
