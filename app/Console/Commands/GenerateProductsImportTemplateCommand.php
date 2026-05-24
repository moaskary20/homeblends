<?php

namespace App\Console\Commands;

use App\Exports\ProductsImportTemplateExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class GenerateProductsImportTemplateCommand extends Command
{
    protected $signature = 'products:import-template';

    protected $description = 'Generate the Excel template for bulk product import in storage/app/public';

    public function handle(): int
    {
        Excel::store(
            new ProductsImportTemplateExport,
            'templates/products-import-template.xlsx',
            'public'
        );

        $url = url('storage/templates/products-import-template.xlsx');
        $this->info("Template: storage/app/public/templates/products-import-template.xlsx");
        $this->info("URL: {$url}");

        return self::SUCCESS;
    }
}
