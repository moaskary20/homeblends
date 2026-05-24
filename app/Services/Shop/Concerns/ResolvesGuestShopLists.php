<?php

namespace App\Services\Shop\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ResolvesGuestShopLists
{
    /**
     * @return array{0: ?User, 1: ?string}
     */
    protected function resolveCustomer(?User $user = null, ?string $sessionId = null): array
    {
        $user ??= auth()->user();

        if ($sessionId === null && request()->hasSession()) {
            $sessionId = (string) request()->session()->getId();
        }

        return [$user, $sessionId ?: null];
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
