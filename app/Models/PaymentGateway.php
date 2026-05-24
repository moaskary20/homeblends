<?php

namespace App\Models;

use App\Enums\PaymentGateway as PaymentGatewayDriver;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'instructions',
        'is_active', 'sort_order', 'config',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function driver(): ?PaymentGatewayDriver
    {
        return PaymentGatewayDriver::tryFrom($this->code);
    }

    public function displayName(): string
    {
        return $this->name ?: ($this->driver()?->label() ?? $this->code);
    }

    public function codFee(): float
    {
        if ($this->code !== PaymentGatewayDriver::CashOnDelivery->value) {
            return 0;
        }

        return max(0, (float) ($this->config['cod_fee'] ?? 0));
    }

    public function minOrderAmount(): ?float
    {
        $min = $this->config['min_order_amount'] ?? null;

        return $min !== null && $min !== '' ? (float) $min : null;
    }

    public function maxOrderAmount(): ?float
    {
        $max = $this->config['max_order_amount'] ?? null;

        return $max !== null && $max !== '' ? (float) $max : null;
    }

    public function isAvailableForAmount(float $orderTotal): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $min = $this->minOrderAmount();
        $max = $this->maxOrderAmount();

        if ($min !== null && $orderTotal < $min) {
            return false;
        }

        if ($max !== null && $orderTotal > $max) {
            return false;
        }

        return $this->driver() !== null;
    }
}
