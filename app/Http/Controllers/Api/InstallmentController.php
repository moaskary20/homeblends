<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstallmentContractResource;
use App\Models\InstallmentContract;
use App\Models\InstallmentPayment;
use App\Services\Installment\InstallmentPaymentService;
use App\Services\Payment\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InstallmentController extends Controller
{
    public function index(Request $request)
    {
        $contracts = InstallmentContract::query()
            ->where('user_id', $request->user()->id)
            ->with(['offer', 'order', 'installments'])
            ->latest()
            ->get();

        return InstallmentContractResource::collection($contracts);
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
            $payment = $service->initiateCustomerPayment($installmentPayment, $driver);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'payment' => $payment,
            'installment' => $installmentPayment->fresh(['contract.installments']),
        ]);
    }
}
