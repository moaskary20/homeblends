<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Services\Review\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(protected ReviewService $reviews) {}

    public function index(string $productSlug)
    {
        $product = Product::where('slug', $productSlug)->firstOrFail();

        $reviews = $product->reviews()
            ->approved()
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }

    public function store(StoreReviewRequest $request, string $productSlug)
    {
        $product = Product::where('slug', $productSlug)->firstOrFail();

        $review = $this->reviews->create(
            $request->user(),
            $product->id,
            $request->integer('rating'),
            $request->comment,
            $request->order_id
        );

        return (new ReviewResource($review))
            ->additional(['message' => __('ecommerce.review_submitted')]);
    }
}
