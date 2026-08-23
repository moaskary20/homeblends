<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Product $product): bool
    {
        return $product->status->value === 'published'
            || $user?->is_admin
            || $user?->can('products.view');
    }

    public function create(User $user): bool
    {
        return $user->is_admin || $user->can('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->is_admin || $user->can('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->is_admin || $user->can('products.delete');
    }
}
