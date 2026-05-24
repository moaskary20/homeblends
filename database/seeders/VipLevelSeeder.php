<?php

namespace Database\Seeders;

use App\Models\VipLevel;
use Illuminate\Database\Seeder;

class VipLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => 'برونزي', 'slug' => 'bronze', 'min_points' => 0, 'discount_percent' => 0, 'sort_order' => 1],
            ['name' => 'فضي', 'slug' => 'silver', 'min_points' => 500, 'discount_percent' => 2, 'sort_order' => 2],
            ['name' => 'ذهبي', 'slug' => 'gold', 'min_points' => 2000, 'discount_percent' => 5, 'sort_order' => 3],
            ['name' => 'بلاتيني', 'slug' => 'platinum', 'min_points' => 5000, 'discount_percent' => 10, 'sort_order' => 4],
        ];

        foreach ($levels as $level) {
            VipLevel::firstOrCreate(['slug' => $level['slug']], $level);
        }
    }
}
