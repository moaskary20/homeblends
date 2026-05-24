<?php

namespace App\Repositories\Eloquent;

use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;

class CouponRepository extends BaseRepository implements CouponRepositoryInterface
{
    public function __construct(Coupon $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->model->newQuery()->where('code', strtoupper($code))->first();
    }
}
