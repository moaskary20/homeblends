<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class RecoverAbandonedCartsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Cart::query()
            ->whereNotNull('abandoned_at')
            ->where('abandoned_at', '<=', now()->subHours(24))
            ->whereHas('items')
            ->with(['user', 'items.product'])
            ->chunkById(50, function ($carts) {
                foreach ($carts as $cart) {
                    if ($cart->user instanceof User && $cart->user->email) {
                        Mail::raw(
                            __('You left items in your cart. Complete your order at HomeBlend Store!'),
                            fn ($message) => $message->to($cart->user->email)->subject(__('Complete your purchase'))
                        );
                    }
                }
            });
    }
}
