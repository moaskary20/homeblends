<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Services\Cart\CartService;
use App\Services\Offer\OfferService;
use App\Services\Seo\SeoService;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index(OfferService $offers)
    {
        return view('shop.offers.index', [
            'offers' => $offers->getActiveOffers(),
            'seo' => app(SeoService::class)->forOffersIndex(),
        ]);
    }

    public function show(string $slug)
    {
        $offer = Offer::query()
            ->where('slug', $slug)
            ->with(['products.offer', 'products.product.images', 'products.product.category', 'products.product.variants', 'products.variant'])
            ->firstOrFail();

        abort_unless($offer->isRunning(), 404);

        $offerProducts = $offer->products
            ->unique(fn ($entry) => $entry->product_id.'-'.($entry->product_variant_id ?? '0'))
            ->values();
        $offer->setRelation('products', $offerProducts);

        $offerTotal = $offer->offerTotal();
        $installmentPlans = $offer->plansForTotal($offerTotal);

        return view('shop.offers.show', [
            'offer' => $offer,
            'offerTotal' => $offerTotal,
            'compareTotal' => $offer->compareTotal(),
            'installmentPlans' => $installmentPlans,
            'monthlyTotal' => $installmentPlans[0]['monthly_amount'] ?? $offer->monthlyAmountFor($offerTotal),
            'canBuySet' => $offer->canPurchaseAsSet(),
            'seo' => app(SeoService::class)->forOffer($offer),
        ]);
    }

    public function addToCart(Request $request, string $slug, CartService $cartService)
    {
        $offer = Offer::query()
            ->where('slug', $slug)
            ->with(['products.product', 'products.variant', 'products.offer'])
            ->firstOrFail();

        abort_unless($offer->isRunning(), 404);

        $months = $request->filled('installment_months')
            ? $request->integer('installment_months')
            : $offer->defaultPlanMonths();

        $cart = $cartService->addOffer($cartService->resolveForRequest($request), $offer, $months);

        return response()->json([
            'totals' => $cartService->getTotals($cart),
        ]);
    }
}
