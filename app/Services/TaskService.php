<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskService
{
    public function __construct(private TaskRepositoryInterface $tasks) {}

    public function listForProject(Project $project, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->tasks->paginateForProject($project, $filters, $perPage);
    }

    public function create(Project $project, array $data): Task
    {
        return $this->tasks->create($project, $data);
    }

    public function update(Task $task, array $data): Task
    {
        return $this->tasks->update($task, $data);
    }

    public function delete(Task $task): void
    {
        $this->tasks->delete($task);
    }
}
