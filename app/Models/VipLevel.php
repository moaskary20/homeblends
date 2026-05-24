<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VipLevel extends Model
{
    protected $fillable = ['name', 'slug', 'min_points', 'discount_percent', 'benefits', 'sort_order'];

    protected function casts(): array
    {
        return [
            'benefits' => 'array',
            'discount_percent' => 'decimal:2',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
