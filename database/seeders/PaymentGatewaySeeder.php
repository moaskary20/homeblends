<?php

namespace Database\Seeders;

use App\Enums\PaymentGateway as PaymentGatewayDriver;
use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'code' => PaymentGatewayDriver::CashOnDelivery->value,
                'name' => 'الدفع عند الاستلام',
                'description' => 'ادفع نقداً عند استلام طلبك من مندوب التوصيل.',
                'instructions' => 'يُرجى تجهيز المبلغ المطلوب بالجنيه المصري. قد تُطبَّق رسوم إضافية للدفع عند الاستلام إن وُجدت.',
                'is_active' => true,
                'sort_order' => 1,
                'config' => ['cod_fee' => 0],
            ],
            [
                'code' => PaymentGatewayDriver::Paypal->value,
                'name' => 'باي بال',
                'description' => 'الدفع الآمن عبر PayPal.',
                'instructions' => null,
                'is_active' => false,
                'sort_order' => 2,
                'config' => [],
            ],
            [
                'code' => PaymentGatewayDriver::LocalProvider->value,
                'name' => 'دفع محلي',
                'description' => 'بوابة دفع محلية (فودافون كاش، إنستاباي، إلخ).',
                'instructions' => null,
                'is_active' => false,
                'sort_order' => 3,
                'config' => [],
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['code' => $gateway['code']],
                $gateway
            );
        }

        app(\App\Services\Payment\PaymentGatewayService::class)->clearCache();
    }
}
