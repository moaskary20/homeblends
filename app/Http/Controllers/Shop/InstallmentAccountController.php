<?php

namespace App\Http\Controllers\Shop;

use App\Enums\PaymentGateway as PaymentGatewayDriver;
use App\Http\Controllers\Controller;
use App\Models\InstallmentContract;
use App\Models\InstallmentPayment;
use App\Services\Installment\InstallmentPaymentService;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Seo\SeoService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InstallmentAccountController extends Controller
{
    public function index(Request $request, PaymentGatewayService $gateways)
    {
        $contracts = InstallmentContract::query()
            ->where('user_id', $request->user()->id)
            ->with(['offer', 'order', 'installments', 'orderItems'])
            ->latest()
            ->get();

        return view('shop.account.installments', [
            'contracts' => $contracts,
            'paymentGateways' => $gateways->getActive()->filter(
                fn ($gateway) => $gateway->driver() !== PaymentGatewayDriver::CashOnDelivery
            ),
            'seo' => app(SeoService::class)->forPrivatePage(__('ecommerce.my_installments')),
        ]);
    }

    public function pay(
        Request $request,
        InstallmentPayment $installmentPayment,
        InstallmentPaymentService $service,
        PaymentGatewayService $gateways,
    ) {
        abort_unless($installmentPayment->contract?->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'payment_gateway' => ['required', 'string'],
        ]);

        try {
            $driver = $gateways->resolveDriver($data['payment_gateway']);
            $service->initiateCustomerPayment($installmentPayment, $driver);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', __('ecommerce.installment_payment_initiated'));
    }
}
