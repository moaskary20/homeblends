<?php

namespace App\Http\Requests\Checkout;

use App\Services\Payment\PaymentGatewayService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string'],
            'shipping_address.last_name' => ['required', 'string'],
            'shipping_address.phone' => ['required', 'string'],
            'shipping_address.address_line_1' => ['required', 'string'],
            'shipping_address.city' => ['required', 'string'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'billing_address' => ['nullable', 'array'],
            'shipping_rate_id' => ['required', 'exists:shipping_rates,id'],
            'coupon_code' => ['nullable', 'string'],
            'payment_gateway' => [
                'required',
                Rule::in(app(PaymentGatewayService::class)->getActiveCodes()),
            ],
            'loyalty_points' => ['integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
