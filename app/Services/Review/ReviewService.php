<?php

namespace App\Services\Review;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function create(User $user, int $productId, int $rating, ?string $comment, ?int $orderId = null): ProductReview
    {
        if ($rating < 1 || $rating > 5) {
            throw ValidationException::withMessages(['rating' => [__('ecommerce.invalid_rating')]]);
        }

        $verified = false;
        if ($orderId) {
            $verified = $user->orders()
                ->whereKey($orderId)
                ->whereHas('items', fn ($q) => $q->where('product_id', $productId))
                ->exists();
        }

        return ProductReview::create([
            'product_id' => $productId,
            'user_id' => $user->id,
            'order_id' => $orderId,
            'rating' => $rating,
            'comment' => $comment,
            'is_approved' => false,
            'is_verified_purchase' => $verified,
        ]);
    }

    public function approve(ProductReview $review): ProductReview
    {
        $review->update(['is_approved' => true]);
        $this->syncProductRating($review->product);

        return $review->fresh();
    }

    public function syncProductRating(Product $product): void
    {
        $stats = $product->reviews()->approved();

        $product->update([
            'avg_rating' => round((float) $stats->avg('rating'), 2),
            'reviews_count' => $stats->count(),
        ]);
    }
}
