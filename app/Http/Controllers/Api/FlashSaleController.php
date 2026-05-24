<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashSaleProductResource;
use App\Http\Resources\FlashSaleResource;
use App\Models\FlashSale;
use App\Services\FlashSale\FlashSaleService;
use Illuminate\Http\JsonResponse;

class FlashSaleController extends Controller
{
    public function __construct(protected FlashSaleService $flashSales) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => FlashSaleResource::collection($this->flashSales->getActiveSales()),
        ]);
    }

    public function products(): JsonResponse
    {
        return response()->json([
            'data' => FlashSaleProductResource::collection(
                $this->flashSales->getHighlightedProducts(12)
            ),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $sale = FlashSale::query()
            ->where('slug', $slug)
            ->with(['products.product.category', 'products.product.images', 'products.variant'])
            ->first();

        if (! $sale || ! $sale->isRunning()) {
            return response()->json(['message' => __('Not found')], 404);
        }

        return response()->json([
            'data' => new FlashSaleResource($sale),
        ]);
    }
}
