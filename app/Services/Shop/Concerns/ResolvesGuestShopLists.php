<?php

namespace App\Services\Shop\Concerns;

use App\Models\User;
use App\Support\GuestShopContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait ResolvesGuestShopLists
{
    /**
     * @return array{0: ?User, 1: ?string}
     */
    protected function resolveCustomer(?User $user = null, ?string $sessionId = null): array
    {
        if ($user === null && $sessionId === null && request() instanceof Request) {
            return GuestShopContext::resolve(request());
        }

        $user ??= auth('web')->user();

        if ($sessionId === null && request()->hasSession()) {
            $sessionId = (string) request()->session()->getId();
        }

        if (! $user) {
            $sessionId = is_string($sessionId) && $sessionId !== '' ? $sessionId : null;
        }

        return [$user, $sessionId];
    }

    protected function assertGuestSessionId(?string $sessionId): string
    {
        if (! is_string($sessionId) || $sessionId === '') {
            throw new \InvalidArgumentException('Guest wishlist/compare requires a browser session.');
        }

        return $sessionId;
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    protected function customerQuery(string $modelClass, ?User $user = null, ?string $sessionId = null): Builder
    {
        [$user, $sessionId] = $this->resolveCustomer($user, $sessionId);

        $query = $modelClass::query();

        if ($user) {
            return $query->where('user_id', $user->id);
        }

        if ($sessionId) {
            return $query->whereNull('user_id')->where('session_id', $sessionId);
        }

        return $query->whereRaw('0 = 1');
    }
}
