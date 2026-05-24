<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Affiliate\AffiliatePayoutService;
use App\Services\Affiliate\AffiliateService;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function apply(Request $request, AffiliateService $affiliates)
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url'],
            'bio' => ['nullable', 'string'],
            'payment_details' => ['nullable', 'array'],
        ]);

        $affiliate = $affiliates->apply($request->user(), $data);

        return response()->json([
            'affiliate' => $affiliate,
            'message' => __('ecommerce.affiliate_application_submitted'),
        ], 201);
    }

    public function dashboard(Request $request)
    {
        $affiliate = $request->user()->affiliate;

        abort_unless($affiliate, 404);

        return response()->json([
            'affiliate' => $affiliate,
            'referral_url' => $affiliate->referralUrl(),
            'stats' => [
                'balance' => $affiliate->balance,
                'total_earned' => $affiliate->total_earned,
                'total_paid' => $affiliate->total_paid,
                'total_clicks' => $affiliate->total_clicks,
                'total_orders' => $affiliate->total_orders,
            ],
        ]);
    }

    public function requestPayout(Request $request, AffiliatePayoutService $payouts)
    {
        $affiliate = $request->user()->affiliate;
        abort_unless($affiliate?->isActive(), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $payout = $payouts->request($affiliate, (float) $data['amount'], $data['notes'] ?? null);

        return response()->json(['payout' => $payout], 201);
    }
}
