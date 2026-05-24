<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetArabicLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('ar');
        Carbon::setLocale('ar_EG');

        return $next($request);
    }
}
