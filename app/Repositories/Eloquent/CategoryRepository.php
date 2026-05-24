<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function getTree(): Collection
    {
        return $this->model->newQuery()
            ->with(['children' => fn ($q) => $q->active()->with('children')])
            ->active()
            ->roots()
            ->orderBy('sort_order')
            ->get();
    }
}
