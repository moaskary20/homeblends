<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\CouponRepository;
use App\Repositories\Eloquent\OrderRepository;
use App\Repositories\Eloquent\ProductRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        ProductRepositoryInterface::class => ProductRepository::class,
        CategoryRepositoryInterface::class => CategoryRepository::class,
        OrderRepositoryInterface::class => OrderRepository::class,
        CouponRepositoryInterface::class => CouponRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->bindings as $abstract => $concrete) {
            $this->app->bind($abstract, fn ($app) => new $concrete($this->resolveModel($abstract)));
        }
    }

    protected function resolveModel(string $interface): mixed
    {
        return match ($interface) {
            ProductRepositoryInterface::class => new Product,
            CategoryRepositoryInterface::class => new Category,
            OrderRepositoryInterface::class => new Order,
            CouponRepositoryInterface::class => new Coupon,
            default => throw new \InvalidArgumentException("Unknown repository: {$interface}"),
        };
    }
}
