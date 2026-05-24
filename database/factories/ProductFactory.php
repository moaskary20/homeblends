<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => str()->slug($name).'-'.fake()->unique()->numerify('###'),
            'sku' => strtoupper(fake()->unique()->bothify('HB-####')),
            'short_description' => fake()->sentence(),
            'regular_price' => fake()->randomFloat(2, 100, 5000),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'status' => ProductStatus::Published,
        ];
    }
}
