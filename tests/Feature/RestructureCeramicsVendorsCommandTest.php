<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestructureCeramicsVendorsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_restructures_flat_cleopatra_and_gemma_categories(): void
    {
        $ceramics = Category::create(['name' => 'سيراميك', 'slug' => 'ceramics', 'is_active' => true]);

        $cleopatraFloor = Category::create([
            'name' => 'Cleopatra — أرضيات',
            'slug' => 'cleopatra-floor',
            'parent_id' => $ceramics->id,
            'is_active' => true,
        ]);

        $gemmaKitchen = Category::create([
            'name' => 'Gemma — مطبخ',
            'slug' => 'gemma-kitchen',
            'parent_id' => $ceramics->id,
            'is_active' => true,
        ]);

        $this->artisan('categories:restructure-ceramics-vendors')
            ->assertSuccessful();

        $cleopatra = Category::query()->where('slug', 'cleopatra')->first();
        $gemma = Category::query()->where('slug', 'gemma')->first();

        $this->assertNotNull($cleopatra);
        $this->assertNotNull($gemma);
        $this->assertSame($ceramics->id, $cleopatra->parent_id);
        $this->assertSame($ceramics->id, $gemma->parent_id);
        $this->assertSame($cleopatra->id, $cleopatraFloor->fresh()->parent_id);
        $this->assertSame($gemma->id, $gemmaKitchen->fresh()->parent_id);
        $this->assertSame('أرضيات', $cleopatraFloor->fresh()->name);
        $this->assertSame('مطبخ', $gemmaKitchen->fresh()->name);
    }
}
