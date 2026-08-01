<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    public function paginateForProject(Project $project, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(Project $project, array $data): Task;

    public function update(Task $task, array $data): Task;

    public function delete(Task $task): void;

    public function statsForUser(User $user): array;

    public function overdueNeedingNotification(): Collection;

    public function markAsNotified(Task $task): void;
}
