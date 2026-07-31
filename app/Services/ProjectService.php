<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function __construct(private ProjectRepositoryInterface $projects) {}

    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->projects->paginateForUser($user, $perPage);
    }

    public function create(User $user, array $data): Project
    {
        return $this->projects->create($user, $data);
    }

    public function update(Project $project, array $data): Project
    {
        return $this->projects->update($project, $data);
    }

    public function delete(Project $project): void
    {
        $this->projects->delete($project);
    }
}
