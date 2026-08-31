<?php

namespace Tests\Feature;

use App\Enums\InstallmentContractStatus;
use App\Enums\OrderItemFulfillmentStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\InstallmentContractResource\Pages\ViewInstallmentContract;
use App\Filament\Resources\InstallmentContractResource\RelationManagers\OrderItemsRelationManager;
use App\Models\InstallmentContract;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderItemFulfillmentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_item_defaults_to_pending(): void
    {
        $item = $this->makeItem();

        $this->assertSame(OrderItemFulfillmentStatus::Pending, $item->fulfillment_status);
        $this->assertSame('pending', $item->fresh()->fulfillment_status->value);
    }

    public function test_purchases_page_shows_each_product_with_its_status(): void
    {
        $user = User::factory()->create();
        $item = $this->makeItem($user, 'كنبة ركنية');
        $item->update(['fulfillment_status' => OrderItemFulfillmentStatus::Delivered]);

        $this->actingAs($user)
            ->get(route('shop.account.purchases'))
            ->assertOk()
            ->assertSee('كنبة ركنية')
            ->assertSee(__('ecommerce.item_status_delivered'));
    }

    public function test_admin_can_set_product_status_on_installment_contract(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $item = $this->makeItem($user, 'طاولة طعام');
        $contract = InstallmentContract::create([
            'order_id' => $item->order_id,
            'user_id' => $user->id,
            'months' => 6,
            'total_amount' => 1200,
            'monthly_amount' => 200,
            'status' => InstallmentContractStatus::Active,
        ]);

        $this->actingAs($admin);

        Livewire::test(OrderItemsRelationManager::class, [
            'ownerRecord' => $contract,
            'pageClass' => ViewInstallmentContract::class,
        ])
            ->assertCanSeeTableRecords([$item])
            ->call('updateTableColumnState', 'fulfillment_status', (string) $item->getKey(), OrderItemFulfillmentStatus::Delivered->value)
            ->assertNotified(__('ecommerce.item_status_updated'));

        $this->assertSame(
            OrderItemFulfillmentStatus::Delivered,
            $item->fresh()->fulfillment_status
        );
    }

    protected function makeItem(?User $user = null, string $name = 'منتج'): OrderItem
    {
        $user ??= User::factory()->create();
        $product = Product::factory()->create();
        $order = Order::create([
            'order_number' => 'HB-FULFILL-'.uniqid(),
            'user_id' => $user->id,
            'status' => OrderStatus::Confirmed,
            'billing_address' => ['country' => 'EG'],
            'shipping_address' => ['country' => 'EG'],
            'subtotal' => 500,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total' => 500,
            'currency' => 'EGP',
            'payment_status' => 'paid',
        ]);

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $name,
            'sku' => $product->sku,
            'quantity' => 1,
            'unit_price' => 500,
            'total' => 500,
        ]);
    }
}
