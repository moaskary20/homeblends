<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'products';
    }

    public function headings(): array
    {
        return [
            'category_slug',
            'category',
            'name',
            'slug',
            'sku',
            'barcode',
            'short_description',
            'full_description',
            'regular_price',
            'discount_price',
            'cost_price',
            'stock_quantity',
            'low_stock_threshold',
            'weight',
            'dimensions',
            'status',
            'is_featured',
            'meta_title',
            'meta_description',
            'main_image',
        ];
    }

    public function array(): array
    {
        return [
            [
                'home-decor',
                'ديكور منزلي',
                'مزهرية سيراميك بيضاء',
                'ceramic-vase-white',
                'HB-VASE-001',
                '6281234567890',
                'مزهرية أنيقة للديكور العصري',
                'مزهرية سيراميك عالية الجودة مناسبة لغرف المعيشة.',
                '450',
                '399',
                '250',
                '25',
                '5',
                '1.2',
                '20x20x35 سم',
                'published',
                '1',
                'مزهرية سيراميك',
                'تسوق مزهرية سيراميك بأفضل سعر',
                '',
            ],
            [
                'kitchen',
                'المطبخ',
                'طقم أكواب زجاج 6 قطع',
                'glass-cups-set-6',
                'HB-CUP-006',
                '',
                'طقم أكواب زجاج شفاف',
                'طقم 6 أكواب زجاج مقاوم للحرارة.',
                '180',
                '',
                '95',
                '40',
                '10',
                '0.8',
                '',
                'published',
                '0',
                '',
                '',
                '',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
