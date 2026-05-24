<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShippingRateResource;
use App\Models\ShippingRate;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function index(Request $request, ShippingService $shipping)
    {
        $request->validate([
            'country' => ['nullable', 'string', 'size:2'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rates = $shipping->getAvailableRates(
            $request->input('country', 'EG'),
            (float) $request->input('subtotal', 0),
            (float) $request->input('weight', 0),
        );

        return ShippingRateResource::collection($rates);
    }

    public function calculate(Request $request, ShippingService $shipping)
    {
        $request->validate([
            'shipping_rate_id' => ['required', 'exists:shipping_rates,id'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        try {
            $result = $shipping->calculate(
                $request->integer('shipping_rate_id'),
                (float) $request->subtotal,
                (float) $request->input('weight', 0),
                $request->input('country', 'EG'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }
}
