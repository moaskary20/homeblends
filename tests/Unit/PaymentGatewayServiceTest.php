<?php

namespace Tests\Unit;

use App\Enums\PaymentGateway as PaymentGatewayDriver;
use App\Models\PaymentGateway;
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentGatewayServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_active_gateways(): void
    {
        PaymentGateway::create([
            'code' => PaymentGatewayDriver::CashOnDelivery->value,
            'name' => 'COD',
            'is_active' => true,
            'sort_order' => 1,
            'config' => [],
        ]);
        PaymentGateway::create([
            'code' => PaymentGatewayDriver::Paypal->value,
            'name' => 'PayPal',
            'is_active' => false,
            'sort_order' => 2,
            'config' => [],
        ]);

        $service = app(PaymentGatewayService::class);
        $service->clearCache();

        $active = $service->getActive();

        $this->assertCount(1, $active);
        $this->assertSame(PaymentGatewayDriver::CashOnDelivery->value, $active->first()->code);
    }

    public function test_cod_fee_applied_from_config(): void
    {
        $gateway = PaymentGateway::create([
            'code' => PaymentGatewayDriver::CashOnDelivery->value,
            'name' => 'COD',
            'is_active' => true,
            'sort_order' => 1,
            'config' => ['cod_fee' => 25],
        ]);

        $this->assertSame(25.0, $gateway->codFee());
    }

    public function test_rejects_inactive_gateway(): void
    {
        PaymentGateway::create([
            'code' => PaymentGatewayDriver::Paypal->value,
            'name' => 'PayPal',
            'is_active' => false,
            'sort_order' => 1,
            'config' => [],
        ]);

        $this->expectException(ValidationException::class);

        app(PaymentGatewayService::class)->resolveDriver(PaymentGatewayDriver::Paypal->value);
    }

    public function test_min_order_amount_constraint(): void
    {
        PaymentGateway::create([
            'code' => PaymentGatewayDriver::CashOnDelivery->value,
            'name' => 'COD',
            'is_active' => true,
            'sort_order' => 1,
            'config' => ['min_order_amount' => 500],
        ]);

        $gateway = PaymentGateway::first();

        $this->assertTrue($gateway->isAvailableForAmount(600));
        $this->assertFalse($gateway->isAvailableForAmount(100));
    }
}
