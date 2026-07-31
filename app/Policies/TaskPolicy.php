<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $this->ownsProject($user, $project);
    }

    public function view(User $user, Task $task): bool
    {
        return $this->ownsTask($user, $task);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->ownsProject($user, $project);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->ownsTask($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->ownsTask($user, $task);
    }

    protected function ownsProject(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    protected function ownsTask(User $user, Task $task): bool
    {
        return $user->id === $task->project->user_id;
    }
}
