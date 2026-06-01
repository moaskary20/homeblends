<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_page_is_available(): void
    {
        $response = $this->get(route('shop.legal.privacy'));

        $response->assertOk();
        $response->assertSee('سياسة الخصوصية', false);
    }

    public function test_terms_page_is_available(): void
    {
        $this->get(route('shop.legal.terms'))
            ->assertOk()
            ->assertSee('الشروط والأحكام', false)
            ->assertSee('سياسة الموقع', false)
            ->assertSee('id="site-policy"', false);
    }

    public function test_return_policy_page_is_available(): void
    {
        $this->get(route('shop.legal.returns'))
            ->assertOk()
            ->assertSee('سياسة الإرجاع', false);
    }

    public function test_delivery_policy_page_is_available(): void
    {
        $this->get(route('shop.legal.shipping'))
            ->assertOk()
            ->assertSee('سياسة التوصيل', false);
    }
}
