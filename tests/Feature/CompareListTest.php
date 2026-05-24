<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\Shop\CompareListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompareListTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_compare_from_product_card_route(): void
    {
        config(['ecommerce.compare.max_items' => 4]);

        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->postJson(route('shop.account.compare.toggle', $product))
            ->assertOk()
            ->assertJson(['added' => true, 'count' => 1]);

        $this->actingAs($user)
            ->postJson(route('shop.account.compare.toggle', $product))
            ->assertOk()
            ->assertJson(['added' => false, 'count' => 0]);
    }

    public function test_compare_page_shows_products(): void
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(2)->create();

        $service = app(CompareListService::class);
        $service->toggle($user, $products[0]);
        $service->toggle($user, $products[1]);

        $this->actingAs($user)
            ->get(route('shop.account.compare'))
            ->assertOk()
            ->assertSee($products[0]->name, false)
            ->assertSee($products[1]->name, false);
    }

    public function test_compare_max_limit_returns_error(): void
    {
        config(['ecommerce.compare.max_items' => 1]);

        $user = User::factory()->create();
        $first = Product::factory()->create();
        $second = Product::factory()->create();

        app(CompareListService::class)->toggle($user, $first);

        $this->actingAs($user)
            ->postJson(route('shop.account.compare.toggle', $second))
            ->assertStatus(422);
    }
}
