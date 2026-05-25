<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class GuestShopContext
{
    /**
     * @return array{0: ?User, 1: ?string}
     */
    public static function resolve(Request $request): array
    {
        $user = auth('web')->user();

        if ($user) {
            return [$user, $request->hasSession() ? $request->session()->getId() : null];
        }

        $sessionId = null;

        if ($request->hasSession()) {
            $sessionId = $request->session()->getId();
        }

        $headerSession = $request->header('X-Shop-Session-Id');
        if (is_string($headerSession) && $headerSession !== '') {
            $sessionId = $headerSession;
        }

        if (! is_string($sessionId) || $sessionId === '') {
            $sessionId = null;
        }

        return [null, $sessionId];
    }

    public static function requireGuestSessionId(?string $sessionId): string
    {
        if (! is_string($sessionId) || $sessionId === '') {
            throw new \InvalidArgumentException('Guest shop actions require a browser session.');
        }

        return $sessionId;
    }
}
