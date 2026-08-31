<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfferProductResource;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use App\Services\Offer\OfferService;
use Illuminate\Http\JsonResponse;

class OfferController extends Controller
{
    public function __construct(protected OfferService $offers) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => OfferResource::collection($this->offers->getActiveOffers()),
        ]);
    }

    public function products(): JsonResponse
    {
        return response()->json([
            'data' => OfferProductResource::collection(
                $this->offers->getHighlightedProducts(12)
            ),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $offer = Offer::query()
            ->where('slug', $slug)
            ->with(['products.product.category', 'products.product.images', 'products.variant'])
            ->first();

        if (! $offer || ! $offer->isRunning()) {
            return response()->json(['message' => __('Not found')], 404);
        }

        return response()->json([
            'data' => new OfferResource($offer),
        ]);
    }
}
