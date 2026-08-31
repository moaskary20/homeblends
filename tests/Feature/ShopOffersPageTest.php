<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Offer;
use App\Models\OfferProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopOffersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_offers_index_and_show_pages(): void
    {
        $offer = Offer::create([
            'name' => 'عرض الصيف',
            'slug' => 'summer-offer',
            'description' => 'تقسيط أثاث',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
            'installment_months' => 10,
        ]);

        $this->get(route('shop.offers.index'))
            ->assertOk()
            ->assertSee('عرض الصيف');

        $this->get(route('shop.offers.show', $offer->slug))
            ->assertOk()
            ->assertSee('تقسيط أثاث');
    }

    public function test_offer_show_is_sold_as_a_complete_set(): void
    {
        $offer = Offer::create([
            'name' => 'ركن الأطفال',
            'slug' => 'kids-corner',
            'description' => 'تقسيط أثاث',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
            'installment_months' => 6,
        ]);

        $chair = Product::factory()->create([
            'name' => 'كرسي سمارتي',
            'regular_price' => 2700,
            'stock_quantity' => 5,
        ]);
        $stool = Product::factory()->create([
            'name' => 'تابوريه سمارتي',
            'regular_price' => 1809,
            'stock_quantity' => 5,
        ]);

        OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $chair->id,
            'offer_price' => 2160,
            'sort_order' => 1,
        ]);
        OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $stool->id,
            'offer_price' => 1800,
            'sort_order' => 2,
        ]);

        $this->get(route('shop.offers.show', $offer->slug))
            ->assertOk()
            ->assertSee('كرسي سمارتي')
            ->assertSee('تابوريه سمارتي')
            ->assertSee('اشترِ العرض كاملاً')
            ->assertSee('3,960.00')
            ->assertSee('تقسيط 6 شهر')
            ->assertSee('660.00 ج.م كل شهر')
            ->assertDontSee('360.00 ج.م كل شهر')
            ->assertDontSee('300.00 ج.م كل شهر')
            ->assertDontSee('اختيار المنتج')
            ->assertDontSee('أضف المحدد إلى السلة');
    }

    public function test_adding_offer_puts_every_product_in_the_cart(): void
    {
        $offer = Offer::create([
            'name' => 'ركن الأطفال',
            'slug' => 'kids-set',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
            'installment_months' => 6,
        ]);

        $chair = Product::factory()->create(['stock_quantity' => 5, 'regular_price' => 2700]);
        $stool = Product::factory()->create(['stock_quantity' => 5, 'regular_price' => 1800]);

        OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $chair->id,
            'offer_price' => 2160,
        ]);
        OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $stool->id,
            'offer_price' => 1800,
        ]);

        $this->postJson(route('shop.offers.cart', $offer->slug))
            ->assertOk()
            ->assertJsonPath('totals.subtotal', 3960)
            ->assertJsonPath('totals.items_count', 1);

        $this->assertSame(2, CartItem::query()->whereNotNull('offer_product_id')->count());
    }

    public function test_cart_shows_the_offer_as_a_single_line(): void
    {
        $offer = Offer::create([
            'name' => 'ركن الأطفال',
            'slug' => 'kids-cart-set',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
            'installment_months' => 6,
        ]);

        $chair = Product::factory()->create([
            'name' => 'كرسي سمارتي',
            'stock_quantity' => 5,
            'regular_price' => 2700,
        ]);
        $stool = Product::factory()->create([
            'name' => 'تابوريه سمارتي',
            'stock_quantity' => 5,
            'regular_price' => 1800,
        ]);

        OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $chair->id,
            'offer_price' => 2160,
        ]);
        OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $stool->id,
            'offer_price' => 1800,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('shop.offers.cart', $offer->slug))->assertOk();

        $this->actingAs($user)->get(route('shop.cart'))
            ->assertOk()
            ->assertSee('ركن الأطفال')
            ->assertSee('طقم كامل')
            ->assertDontSee('كرسي سمارتي', false)
            ->assertDontSee('تابوريه سمارتي', false);

        $preview = $this->actingAs($user)->getJson(route('shop.cart.preview'))
            ->assertOk()
            ->assertJsonPath('totals.items_count', 1)
            ->assertJsonPath('cart.items.0.is_offer_set', true)
            ->assertJsonPath('cart.items.0.offer.name', 'ركن الأطفال')
            ->assertJsonCount(1, 'cart.items');

        $lineId = $preview->json('cart.items.0.id');

        $this->actingAs($user)->deleteJson('/cart/items/'.$lineId)
            ->assertOk()
            ->assertJsonPath('totals.items_count', 0);

        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_customer_can_choose_an_installment_plan(): void
    {
        $offer = Offer::create([
            'name' => 'ركن الأطفال',
            'slug' => 'kids-plans',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
            'installment_months' => 6,
            'installment_plans' => [6, 12],
        ]);

        $chair = Product::factory()->create(['stock_quantity' => 5, 'regular_price' => 2700]);
        $stool = Product::factory()->create(['stock_quantity' => 5, 'regular_price' => 1800]);

        OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $chair->id,
            'offer_price' => 2160,
        ]);
        OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $stool->id,
            'offer_price' => 1800,
        ]);

        $this->get(route('shop.offers.show', $offer->slug))
            ->assertOk()
            ->assertSee('اختر نظام التقسيط')
            ->assertSee('تقسيط 6 شهر')
            ->assertSee('تقسيط 12 شهر')
            ->assertSee('660.00 ج.م كل شهر')
            ->assertSee('330.00 ج.م كل شهر');

        $this->postJson(route('shop.offers.cart', $offer->slug), [
            'installment_months' => 12,
        ])->assertOk();

        $cartId = CartItem::query()->whereNotNull('offer_product_id')->value('cart_id');
        $this->assertSame(12, (int) Cart::query()->whereKey($cartId)->value('installment_months'));

        $this->postJson(route('shop.offers.cart', $offer->slug), [
            'installment_months' => 24,
        ])->assertStatus(422);
    }

    public function test_single_offer_product_cannot_be_added_to_cart(): void
    {
        $offer = Offer::create([
            'name' => 'ركن الأطفال',
            'slug' => 'kids-single',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
            'installment_months' => 6,
        ]);
        $product = Product::factory()->create(['stock_quantity' => 5]);
        $entry = OfferProduct::create([
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'offer_price' => 1800,
        ]);

        $this->postJson(route('shop.cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'offer_product_id' => $entry->id,
        ])->assertStatus(422);
    }

    public function test_ended_offer_returns_404(): void
    {
        Offer::create([
            'name' => 'منتهٍ',
            'slug' => 'ended',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
            'is_active' => true,
            'installment_months' => 6,
        ]);

        $this->get(route('shop.offers.show', 'ended'))->assertNotFound();
    }
}
