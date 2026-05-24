<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;

trait ResolvesShopCustomer
{
    protected function resolveShopUserId(Request $request): ?int
    {
        if ($request->hasSession()) {
            return auth('web')->id();
        }

        return $request->user()?->id;
    }
}
