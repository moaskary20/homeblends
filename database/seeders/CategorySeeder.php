<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('categories:setup-main');
    }
}
