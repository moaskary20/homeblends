<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DepartmentSubcategoriesSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('categories:setup-subcategories');

        $output = trim(Artisan::output());
        if ($output !== '' && $this->command !== null) {
            $this->command->getOutput()->write($output.PHP_EOL);
        }
    }
}
