<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = ['name', 'countries', 'regions', 'is_active'];

    protected function casts(): array
    {
        return [
            'countries' => 'array',
            'regions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function freeShippingRules(): HasMany
    {
        return $this->hasMany(FreeShippingRule::class);
    }
}
