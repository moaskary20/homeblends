<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);

        return response()->json([
            'url' => Socialite::driver($provider)->stateless()->redirect()->getTargetUrl(),
        ]);
    }

    public function callback(Request $request, string $provider, CartService $cartService)
    {
        $this->validateProvider($provider);

        $socialUser = Socialite::driver($provider)->stateless()->user();

        $account = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($account) {
            $user = $account->user;
        } else {
            $user = User::firstOrCreate(
                ['email' => $socialUser->getEmail()],
                [
                    'name' => $socialUser->getName() ?? 'مستخدم',
                    'password' => Hash::make(Str::random(32)),
                    'locale' => 'ar',
                    'currency' => 'EGP',
                    'email_verified_at' => now(),
                ]
            );

            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);
        }

        if ($request->session_id) {
            $cartService->mergeGuestCart($request->session_id, $user->id);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    protected function validateProvider(string $provider): void
    {
        if (! in_array($provider, ['google', 'facebook'], true)) {
            abort(404);
        }
    }
}
