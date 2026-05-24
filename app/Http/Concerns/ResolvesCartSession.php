<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;

trait ResolvesCartSession
{
    /**
     * Prefer the live Laravel session cookie over a stale X-Session-Id from cached HTML.
     */
    protected function resolveCartSessionId(Request $request): ?string
    {
        if ($request->hasSession()) {
            return $request->session()->getId();
        }

        $header = $request->header('X-Session-Id');

        return is_string($header) && $header !== '' ? $header : null;
    }
}
