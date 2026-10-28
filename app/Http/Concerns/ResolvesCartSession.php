<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;

trait ResolvesCartSession
{
    public const GUEST_SESSION_ID_KEY = 'hb_guest_session_id';

    /**
     * Resolve the cart session key for API and web clients.
     *
     * Mobile apps send X-Session-Id without Laravel cookies. When the API
     * middleware starts an ephemeral in-memory session, we must still honour
     * the explicit header instead of a throwaway session id.
     */
    protected function resolveCartSessionId(Request $request): ?string
    {
        $header = $this->normalizeCartSessionId($request->header('X-Session-Id'))
            ?? $this->normalizeCartSessionId($request->header('X-Shop-Session-Id'));

        $sessionCookie = config('session.cookie');
        $hasSessionCookie = is_string($sessionCookie)
            && $sessionCookie !== ''
            && $request->hasCookie($sessionCookie);

        if ($header !== null && ! $hasSessionCookie) {
            return $header;
        }

        if ($request->hasSession()) {
            $sessionId = $this->normalizeCartSessionId($request->session()->getId()) ?? $header;
            $this->rememberGuestCartSessionId($request, $sessionId);

            return $sessionId;
        }

        return $header;
    }

    /**
     * Session regenerate on login changes the id, but session data is kept.
     * Remember the pre-login id so guest cart/wishlist/compare can still merge.
     */
    protected function rememberGuestCartSessionId(Request $request, ?string $sessionId): void
    {
        if ($sessionId === null || ! $request->hasSession()) {
            return;
        }

        if ($request->user() !== null || auth('web')->id() !== null) {
            return;
        }

        $request->session()->put(self::GUEST_SESSION_ID_KEY, $sessionId);
    }

    protected function rememberedGuestCartSessionId(Request $request): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }

        return $this->normalizeCartSessionId($request->session()->get(self::GUEST_SESSION_ID_KEY))
            ?? $this->normalizeCartSessionId($request->session()->getId());
    }

    protected function normalizeCartSessionId(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
