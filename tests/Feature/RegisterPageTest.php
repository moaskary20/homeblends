<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_succeeds_with_matching_password_confirmation(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'عميل جديد',
            'email' => 'new-customer@example.com',
            'password' => 'SecretPass123!',
            'password_confirmation' => 'SecretPass123!',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new-customer@example.com',
            'name' => 'عميل جديد',
        ]);

        Notification::assertSentTo(
            User::query()->where('email', 'new-customer@example.com')->first(),
            VerifyEmail::class,
        );
    }

    public function test_verification_verify_route_is_registered(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('verification.verify'),
            'Fortify email verification routes must be enabled because User implements MustVerifyEmail.'
        );
    }
}
