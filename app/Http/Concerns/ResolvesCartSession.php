<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;

trait ResolvesCartSession
{
    /**
     * Resolve the cart session key for API and web clients.
     *
     * Mobile apps send X-Session-Id without Laravel cookies. When the API
     * middleware starts an ephemeral in-memory session, we must still honour
     * the explicit header instead of a throwaway session id.
     */
    protected function resolveCartSessionId(Request $request): ?string
    {
        $header = $this->normalizeCartSessionId($request->header('X-Session-Id'));

        $sessionCookie = config('session.cookie');
        $hasSessionCookie = is_string($sessionCookie)
            && $sessionCookie !== ''
            && $request->hasCookie($sessionCookie);

        if ($header !== null && ! $hasSessionCookie) {
            return $header;
        }

        if ($request->hasSession()) {
            return $this->normalizeCartSessionId($request->session()->getId()) ?? $header;
        }

        return $header;
    }

    protected function normalizeCartSessionId(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
