<?php

namespace App\Repositories\Contracts;

interface CouponRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?\Illuminate\Database\Eloquent\Model;
}
