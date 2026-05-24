<?php

namespace App\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug) && ! empty($model->name)) {
                $model->slug = static::generateUniqueSlug($model->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = static::slugify($name);
        $original = $slug;
        $count = 1;

        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $original.'-'.$count++;
        }

        return $slug;
    }

    public static function slugify(string $name): string
    {
        $slug = Str::slug($name);

        if ($slug !== '') {
            return $slug;
        }

        $slug = preg_replace('/\s+/u', '-', trim($name));
        $slug = preg_replace('/[^\p{L}\p{N}\-_]+/u', '', $slug ?? '');

        return $slug !== '' ? $slug : 'item-'.Str::lower(Str::random(8));
    }
}
