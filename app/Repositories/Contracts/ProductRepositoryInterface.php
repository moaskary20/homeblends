<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function getFeatured(int $limit = 8): Collection;

    public function search(string $term, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator;
}
