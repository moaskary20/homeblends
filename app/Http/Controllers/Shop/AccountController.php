<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Support\ProductCompareBuilder;
use App\Support\ProductMedia;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Seo\SeoService;
use App\Services\Shop\CompareListService;
use App\Services\Shop\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function profile(Request $request)
    {
        return view('shop.account.profile', [
            'user' => $request->user(),
            'seo' => app(SeoService::class)->forPrivatePage(__('ecommerce.my_account')),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $request->user()->update($data);

        return back()->with('success', __('ecommerce.profile_updated'));
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::default()],
        ], [
            'current_password.current_password' => __('ecommerce.current_password_invalid'),
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return back()->with('success', __('ecommerce.password_updated'));
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $this->deleteStoredAvatar($user->avatar);

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return back()->with('success', __('ecommerce.avatar_updated'));
    }

    public function removeAvatar(Request $request)
    {
        $user = $request->user();
        $this->deleteStoredAvatar($user->avatar);
        $user->update(['avatar' => null]);

        return back()->with('success', __('ecommerce.avatar_removed'));
    }

    private function deleteStoredAvatar(?string $avatar): void
    {
        if ($avatar && ! str_starts_with($avatar, 'http')) {
            Storage::disk('public')->delete($avatar);
        }
    }

    public function purchases(Request $request)
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('shop.account.purchases', [
            'orders' => $orders,
            'seo' => app(SeoService::class)->forPrivatePage(__('ecommerce.my_purchases')),
        ]);
    }

    public function tracking(Request $request)
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['statusHistory' => fn ($q) => $q->orderBy('created_at')])
            ->latest()
            ->paginate(10);

        return view('shop.account.tracking', [
            'orders' => $orders,
            'seo' => app(SeoService::class)->forPrivatePage(__('ecommerce.track_orders')),
        ]);
    }

    public function points(Request $request, LoyaltyService $loyalty)
    {
        $user = $request->user()->load('vipLevel');

        return view('shop.account.points', [
            'user' => $user,
            'program' => $loyalty->getProgramInfo($user),
            'transactions' => $user->loyaltyTransactions()
                ->with('order:id,order_number')
                ->latest()
                ->limit(50)
                ->get(),
            'seo' => app(SeoService::class)->forPrivatePage(__('ecommerce.my_points')),
        ]);
    }

    public function redeemPoints(Request $request, LoyaltyService $loyalty)
    {
        $minRedeem = (int) config('ecommerce.loyalty.min_redeem_points', 10);

        $request->validate([
            'points' => ['required', 'integer', 'min:'.$minRedeem],
        ]);

        try {
            $amount = $loyalty->redeemToWallet($request->user(), $request->integer('points'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('shop.account.points')
            ->with('success', __('ecommerce.points_redeemed_to_wallet', [
                'amount' => number_format($amount, 2),
            ]));
    }

    public function favorites(Request $request, WishlistService $wishlist)
    {
        return view('shop.account.favorites', [
            'products' => $wishlist->products($request->user()),
            'seo' => app(SeoService::class)->forPrivatePage(__('ecommerce.my_favorites')),
        ]);
    }

    public function favoritesPreview(Request $request, WishlistService $wishlist)
    {
        $user = $request->user();
        $preview = $wishlist->previewProducts($user, 5);
        $count = $wishlist->count($user);

        return response()->json([
            'count' => $count,
            'has_more' => $count > $preview->count(),
            'items' => $preview->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'thumb' => ProductMedia::productThumbnail($product),
                'price' => (float) $product->effective_price,
                'url' => route('shop.products.show', $product->slug),
                'remove_url' => route('shop.account.favorites.remove', $product),
            ])->values(),
        ]);
    }

    public function toggleFavorite(Request $request, Product $product, WishlistService $wishlist)
    {
        $added = $wishlist->toggle($request->user(), $product);

        if ($request->expectsJson()) {
            return response()->json([
                'added' => $added,
                'count' => $wishlist->count($request->user()),
            ]);
        }

        return back()->with('success', $added
            ? __('ecommerce.added_to_favorites')
            : __('ecommerce.removed_from_favorites'));
    }

    public function removeFavorite(Request $request, Product $product, WishlistService $wishlist)
    {
        $wishlist->remove($request->user(), $product);

        if ($request->expectsJson()) {
            return response()->json([
                'count' => $wishlist->count($request->user()),
            ]);
        }

        return back()->with('success', __('ecommerce.removed_from_favorites'));
    }

    public function compare(Request $request, CompareListService $compare, ProductCompareBuilder $builder)
    {
        $built = $builder->build($compare->products($request->user()));

        return view('shop.account.compare', [
            'products' => $built['products'],
            'rows' => $built['rows'],
            'maxItems' => $compare->maxItems(),
            'seo' => app(SeoService::class)->forPrivatePage(__('ecommerce.my_compare')),
        ]);
    }

    public function removeCompare(Request $request, Product $product, CompareListService $compare)
    {
        $compare->remove($request->user(), $product);

        if ($request->expectsJson()) {
            return response()->json([
                'count' => $compare->count($request->user()),
            ]);
        }

        return back()->with('success', __('ecommerce.removed_from_compare'));
    }

    public function toggleCompare(Request $request, Product $product, CompareListService $compare)
    {
        try {
            $added = $compare->toggle($request->user(), $product);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'added' => $added,
                'count' => $compare->count($request->user()),
            ]);
        }

        return back()->with('success', $added
            ? __('ecommerce.added_to_compare')
            : __('ecommerce.removed_from_compare'));
    }

    public function clearCompare(Request $request, CompareListService $compare)
    {
        $compare->clear($request->user());

        return back()->with('success', __('ecommerce.compare_cleared'));
    }
}
