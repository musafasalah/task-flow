<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator;

    public function create(User $user, array $data): Project;

    public function update(Project $project, array $data): Project;

    public function delete(Project $project): void;

    public function statsForUser(User $user): array;
}
