<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface RepositoryInterface
{
    public function queryVisibleTo(User $user): Builder;

    public function listForUser(User $user, Request $request, array $with = []): LengthAwarePaginator;

    public function findForUserOrFail(User $user, int $id, array $with = []): Model;

    public function allVisibleTo(User $user, array $with = []): Collection;

    public function applyFilters(Builder $query, Request $request): Builder;

    public function applySearch(Builder $query, Request $request): Builder;

    public function applySorting(Builder $query, Request $request): Builder;
}
