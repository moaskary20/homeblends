<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements RepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->get($columns);
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->model->newQuery(), $filters)->paginate($perPage);
    }

    public function find(int|string $id, array $relations = []): ?Model
    {
        return $this->model->newQuery()->with($relations)->find($id);
    }

    public function findBySlug(string $slug, array $relations = []): ?Model
    {
        return $this->model->newQuery()->with($relations)->where('slug', $slug)->first();
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int|string $id, array $data): Model
    {
        $record = $this->model->newQuery()->findOrFail($id);
        $record->update($data);

        return $record->fresh();
    }

    public function delete(int|string $id): bool
    {
        return (bool) $this->model->newQuery()->whereKey($id)->delete();
    }

    protected function applyFilters($query, array $filters)
    {
        return $query;
    }
}
