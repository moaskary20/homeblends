<?php

namespace App\Services\Affiliate;

use App\Models\Affiliate;
use App\Models\AffiliateClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AffiliateTrackingService
{
    public function captureFromRequest(Request $request): ?Affiliate
    {
        if ($request->has('ref')) {
            return $this->trackNewReferral($request, strtoupper((string) $request->query('ref')));
        }

        $code = $request->cookie(config('affiliate.cookie_name'));
        if (! $code) {
            return null;
        }

        $affiliate = Affiliate::query()->active()->where('code', strtoupper($code))->first();

        if ($affiliate) {
            $this->rememberInSession($request, $affiliate, $request->session()->get('affiliate_click_id'));
        }

        return $affiliate;
    }

    protected function trackNewReferral(Request $request, string $code): ?Affiliate
    {
        $affiliate = Affiliate::query()->active()->where('code', $code)->first();

        if (! $affiliate) {
            return null;
        }

        $click = AffiliateClick::create([
            'affiliate_id' => $affiliate->id,
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'landing_url' => $request->fullUrl(),
            'referrer_url' => $request->headers->get('referer'),
        ]);

        $affiliate->increment('total_clicks');

        $this->rememberInSession($request, $affiliate, $click->id);

        Cookie::queue(
            config('affiliate.cookie_name'),
            $affiliate->code,
            config('affiliate.cookie_days') * 24 * 60
        );

        return $affiliate;
    }

    protected function rememberInSession(Request $request, Affiliate $affiliate, mixed $clickId = null): void
    {
        $request->session()->put('affiliate_id', $affiliate->id);

        if ($clickId) {
            $request->session()->put('affiliate_click_id', $clickId);
        }
    }

    public function resolveForCheckout(?int $customerUserId): ?array
    {
        $affiliateId = session('affiliate_id');
        $clickId = session('affiliate_click_id');

        if (! $affiliateId) {
            $code = request()->cookie(config('affiliate.cookie_name'));
            if ($code) {
                $affiliate = Affiliate::query()->active()->where('code', strtoupper($code))->first();
                $affiliateId = $affiliate?->id;
            }
        }

        if (! $affiliateId) {
            return null;
        }

        $affiliate = Affiliate::query()->active()->find($affiliateId);

        if (! $affiliate) {
            return null;
        }

        if ($customerUserId && $affiliate->user_id === $customerUserId) {
            return null;
        }

        return [
            'affiliate_id' => $affiliate->id,
            'affiliate_click_id' => $clickId,
        ];
    }

    public function clearSession(): void
    {
        session()->forget(['affiliate_id', 'affiliate_click_id']);
    }
}
