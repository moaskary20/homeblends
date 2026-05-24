<?php

namespace App\Http\Middleware;

use App\Services\Affiliate\AffiliateTrackingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAffiliateReferral
{
    public function __construct(protected AffiliateTrackingService $tracking) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('ref') || $request->cookie(config('affiliate.cookie_name'))) {
            $this->tracking->captureFromRequest($request);
        }

        return $next($request);
    }
}
