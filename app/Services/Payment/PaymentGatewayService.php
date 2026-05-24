<?php

namespace App\Services\Payment;

use App\Enums\PaymentGateway as PaymentGatewayDriver;
use App\Models\PaymentGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class PaymentGatewayService
{
    public function getActive(): Collection
    {
        return Cache::remember('payment_gateways.active', 300, function () {
            return PaymentGateway::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->filter(fn (PaymentGateway $gateway) => $gateway->driver() !== null);
        });
    }

    public function getActiveCodes(): array
    {
        return $this->getActive()->pluck('code')->all();
    }

    public function findByCode(string $code): ?PaymentGateway
    {
        return PaymentGateway::query()->where('code', $code)->first();
    }

    public function resolveDriver(string $code): PaymentGatewayDriver
    {
        $gateway = $this->findByCode($code);

        if (! $gateway || ! $gateway->is_active) {
            throw ValidationException::withMessages([
                'payment_gateway' => [__('ecommerce.payment_gateway_unavailable')],
            ]);
        }

        $driver = $gateway->driver();

        if (! $driver) {
            throw ValidationException::withMessages([
                'payment_gateway' => [__('ecommerce.payment_gateway_invalid')],
            ]);
        }

        return $driver;
    }

    public function assertAvailableForOrder(string $code, float $orderTotal): PaymentGateway
    {
        $gateway = $this->findByCode($code);

        if (! $gateway || ! $gateway->isAvailableForAmount($orderTotal)) {
            throw ValidationException::withMessages([
                'payment_gateway' => [__('ecommerce.payment_gateway_unavailable')],
            ]);
        }

        return $gateway;
    }

    public function clearCache(): void
    {
        Cache::forget('payment_gateways.active');
    }
}
