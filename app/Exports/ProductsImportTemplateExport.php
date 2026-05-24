<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductsImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ProductsTemplateExport,
            new ProductsImportGuideExport,
        ];
    }
}
