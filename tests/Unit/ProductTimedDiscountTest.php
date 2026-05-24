<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTimedDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_applies_within_schedule(): void
    {
        $product = Product::factory()->create([
            'regular_price' => 1000,
            'discount_price' => 750,
            'discount_starts_at' => now()->subHour(),
            'discount_ends_at' => now()->addDay(),
        ]);

        $this->assertTrue($product->hasActiveTimedDiscount());
        $this->assertSame(750.0, $product->baseSellingPrice());
        $this->assertSame(750.0, $product->effective_price);
    }

    public function test_discount_not_applied_before_start(): void
    {
        $product = Product::factory()->create([
            'regular_price' => 1000,
            'discount_price' => 750,
            'discount_starts_at' => now()->addDay(),
            'discount_ends_at' => now()->addDays(2),
        ]);

        $this->assertFalse($product->hasActiveTimedDiscount());
        $this->assertSame(1000.0, $product->effective_price);
    }

    public function test_permanent_discount_without_dates(): void
    {
        $product = Product::factory()->create([
            'regular_price' => 500,
            'discount_price' => 400,
            'discount_starts_at' => null,
            'discount_ends_at' => null,
        ]);

        $this->assertTrue($product->hasActiveTimedDiscount());
        $this->assertSame(400.0, $product->effective_price);
    }
}
