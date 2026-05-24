<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_pages_require_auth(): void
    {
        $this->get(route('shop.account.profile'))->assertRedirect();
    }

    public function test_authenticated_user_can_view_account_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('shop.account.profile'))->assertOk();
        $this->actingAs($user)->get(route('shop.account.purchases'))->assertOk();
        $this->actingAs($user)->get(route('shop.account.points'))->assertOk();
        $this->actingAs($user)->get(route('shop.account.tracking'))->assertOk();
        $this->actingAs($user)->get(route('shop.account.favorites'))->assertOk();
        $this->actingAs($user)->get(route('shop.account.compare'))->assertOk();
    }

    public function test_user_can_update_password_on_profile(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user)
            ->from(route('shop.account.profile'))
            ->put(route('shop.account.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertRedirect(route('shop.account.profile'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_user_can_redeem_points_to_wallet(): void
    {
        config([
            'ecommerce.loyalty.point_value' => 0.1,
            'ecommerce.loyalty.min_redeem_points' => 10,
        ]);

        $user = User::factory()->create(['loyalty_points' => 80]);

        $this->actingAs($user)
            ->from(route('shop.account.points'))
            ->post(route('shop.account.points.redeem'), ['points' => 20])
            ->assertRedirect(route('shop.account.points'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame(60, $user->loyalty_points);
        $this->assertSame(2.0, (float) $user->store_credit);
    }

    public function test_favorites_preview_returns_json(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->actingAs($user)
            ->getJson(route('shop.account.favorites.preview'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonCount(1, 'items');
    }

    public function test_user_can_upload_and_remove_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('shop.account.profile'))
            ->post(route('shop.account.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertRedirect(route('shop.account.profile'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);

        $this->actingAs($user)
            ->from(route('shop.account.profile'))
            ->delete(route('shop.account.avatar.remove'))
            ->assertRedirect(route('shop.account.profile'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertNull($user->avatar);
    }
}
