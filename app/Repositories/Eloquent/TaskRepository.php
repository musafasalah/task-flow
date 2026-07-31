<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskRepository implements TaskRepositoryInterface
{
    public function paginateForProject(Project $project, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $project->tasks()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(Project $project, array $data): Task
    {
        return $project->tasks()->create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->refresh();
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
