<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAffiliate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->affiliate || ! $user->affiliate->isActive()) {
            abort(403, __('ecommerce.affiliate_access_denied'));
        }

        return $next($request);
    }
}
