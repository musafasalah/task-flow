<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;

class DashboardService
{
    public function __construct(
        private ProjectRepositoryInterface $projects,
        private TaskRepositoryInterface $tasks,
    ) {}

    public function forUser(User $user): array
    {
        $projectStats = $this->projects->statsForUser($user);
        $taskStats = $this->tasks->statsForUser($user);

        return [
            'total_projects' => $projectStats['total'],
            'active_projects' => $projectStats['active'],
            'total_tasks' => $taskStats['total'],
            'completed_tasks' => $taskStats['completed'],
            'pending_tasks' => $taskStats['pending'],
            'overdue_tasks' => $taskStats['overdue'],
        ];
    }
}
