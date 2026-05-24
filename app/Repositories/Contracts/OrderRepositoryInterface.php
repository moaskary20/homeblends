<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface extends RepositoryInterface
{
    public function forUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function getSalesStats(string $period = 'month'): array;
}
