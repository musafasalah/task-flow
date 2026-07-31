<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    public function paginateForProject(Project $project, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(Project $project, array $data): Task;

    public function update(Task $task, array $data): Task;
    
    public function delete(Task $task): void;
}
