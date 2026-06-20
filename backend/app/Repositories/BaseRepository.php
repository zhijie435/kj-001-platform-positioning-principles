<?php

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class BaseRepository implements RepositoryInterface
{
    protected string $modelClass;

    protected array $searchColumns = [];

    protected array $filterColumns = [];

    protected string $defaultSortColumn = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public function __construct()
    {
        $this->boot();
    }

    protected function boot(): void
    {
    }

    public function queryVisibleTo(User $user): Builder
    {
        $model = $this->newModel();

        if (method_exists($model, 'scopeVisibleTo')) {
            return $model->visibleTo($user);
        }

        return $model->newQuery();
    }

    public function listForUser(User $user, Request $request, array $with = []): LengthAwarePaginator
    {
        $query = $this->queryVisibleTo($user)->with($with);

        $this->applyFilters($query, $request);
        $this->applySearch($query, $request);
        $this->applySorting($query, $request);

        return $query->paginate($this->getPerPage($request));
    }

    public function findForUserOrFail(User $user, int $id, array $with = []): Model
    {
        return $this->queryVisibleTo($user)
            ->with($with)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function allVisibleTo(User $user, array $with = []): Collection
    {
        $query = $this->queryVisibleTo($user)->with($with);
        $this->applySorting($query, request());

        return $query->get();
    }

    public function applyFilters(Builder $query, Request $request): Builder
    {
        foreach ($this->filterColumns as $column => $config) {
            $config = is_array($config) ? $config : ['column' => $config];
            $param = $config['param'] ?? $column;

            if (!$request->filled($param)) {
                continue;
            }

            $this->applySingleFilter($query, $request, $column, $config, $param);
        }

        return $query;
    }

    protected function applySingleFilter(
        Builder $query,
        Request $request,
        string $column,
        array $config,
        string $param
    ): void {
        $operator = $config['operator'] ?? '=';
        $value = $request->input($param);
        $type = $config['type'] ?? 'string';

        if ($type === 'int') {
            $value = (int) $value;
        } elseif ($type === 'bool') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } elseif ($type === 'date_from') {
            $operator = '>=';
        } elseif ($type === 'date_to') {
            $operator = '<=';
        }

        if ($operator === 'in') {
            $values = is_array($value) ? $value : explode(',', (string) $value);
            $query->whereIn($column, $values);
        } else {
            $query->where($column, $operator, $value);
        }
    }

    public function applySearch(Builder $query, Request $request): Builder
    {
        $keyword = trim((string) $request->input('search', ''));

        if ($keyword === '' || empty($this->searchColumns)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            foreach ($this->searchColumns as $column) {
                $q->orWhere($column, 'like', "%{$keyword}%");
            }
        });
    }

    public function applySorting(Builder $query, Request $request): Builder
    {
        $sortColumn = $request->input('sort_by', $this->defaultSortColumn);
        $sortDirection = strtolower($request->input('sort_dir', $this->defaultSortDirection));

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = $this->defaultSortDirection;
        }

        $allowedColumns = $this->getSortableColumns();
        $sortColumn = in_array($sortColumn, $allowedColumns, true) ? $sortColumn : $this->defaultSortColumn;

        if (Str::contains($sortColumn, '.')) {
            [$relation, $field] = explode('.', $sortColumn, 2);

            return $query->whereHas($relation, function (Builder $q) use ($field, $sortDirection) {
                $q->orderBy($field, $sortDirection);
            });
        }

        return $query->orderBy($sortColumn, $sortDirection);
    }

    protected function getSortableColumns(): array
    {
        $model = $this->newModel();
        $fillable = method_exists($model, 'getFillable') ? $model->getFillable() : [];

        return array_merge($fillable, ['id', 'created_at', 'updated_at', $this->defaultSortColumn]);
    }

    protected function getPerPage(Request $request): int
    {
        $perPage = (int) $request->integer('per_page', 15);

        return max(1, min($perPage, 100));
    }

    protected function newModel(): Model
    {
        return new $this->modelClass();
    }
}
