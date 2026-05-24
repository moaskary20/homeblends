<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('مسودة'),
            self::Published => __('منشور'),
            self::Archived => __('مؤرشف'),
        };
    }
}
