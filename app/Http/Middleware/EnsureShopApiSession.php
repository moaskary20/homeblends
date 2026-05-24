<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarantees a Laravel session for shop API calls (cart, coupons, etc.)
 * even when Sanctum does not treat the request as "stateful".
 */
class EnsureShopApiSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()) {
            return $next($request);
        }

        return (new Pipeline(app()))->send($request)->through([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
        ])->then(fn (Request $request) => $next($request));
    }
}
