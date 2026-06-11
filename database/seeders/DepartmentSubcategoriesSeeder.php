<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DepartmentSubcategoriesSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('categories:setup-subcategories');
        $this->command?->write(Artisan::output());
    }
}
