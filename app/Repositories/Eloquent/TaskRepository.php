<?php

namespace App\Repositories\Eloquent;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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

    public function statsForUser(User $user): array
    {
        $forUser = fn (): Builder => Task::whereHas(
            'project',
            fn (Builder $query) => $query->where('user_id', $user->id),
        );

        return [
            'total' => $forUser()->count(),
            'completed' => $forUser()->where('status', TaskStatus::Done)->count(),
            'pending' => $forUser()->where('status', '!=', TaskStatus::Done)->count(),
            'overdue' => $forUser()->overdue()->count(),
        ];
    }
}
