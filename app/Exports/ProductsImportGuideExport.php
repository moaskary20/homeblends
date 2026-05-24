<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProductsImportGuideExport implements FromArray, WithTitle
{
    public function title(): string
    {
        return 'instructions';
    }

    public function array(): array
    {
        return [
            ['دليل استيراد المنتجات — هوم بلند'],
            [''],
            ['الأعمدة المطلوبة: sku, name, regular_price'],
            ['category_slug: رابط التصنيف (يُنشأ تلقائياً إن لم يوجد)'],
            ['category: اسم التصنيف بالعربية'],
            ['status: published | draft | archived'],
            ['is_featured: 1 أو 0'],
            [''],
            ['عند التكرار: يتم تحديث المنتج بنفس sku'],
            ['لا تحذف صف العناوين (الصف الأول)'],
            ['احفظ الملف بصيغة xlsx أو csv'],
        ];
    }
}
