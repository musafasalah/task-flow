<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('returns aggregated statistics scoped to the authenticated user', function () {
    $activeA = Project::factory()->for($this->user)->active()->create();
    $activeB = Project::factory()->for($this->user)->active()->create();
    Project::factory()->for($this->user)->completed()->create();

    // 2 completed tasks
    Task::factory(2)->for($activeA)->create(['status' => TaskStatus::Done, 'due_date' => null]);
    // 2 pending, not overdue (future due date)
    Task::factory(2)->for($activeA)->create(['status' => TaskStatus::Todo, 'due_date' => now()->addWeek()]);
    // 2 overdue (past due, not done) -> also pending
    Task::factory(2)->for($activeB)->overdue()->create();

    // Another user's data must be excluded
    $other = Project::factory()->create();
    Task::factory(5)->for($other)->overdue()->create();

    $this->getJson('/api/dashboard')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'total_projects' => 3,
                'active_projects' => 2,
                'total_tasks' => 6,
                'completed_tasks' => 2,
                'pending_tasks' => 4,
                'overdue_tasks' => 2,
            ],
        ]);
});

it('returns zeroed statistics for a user with no data', function () {
    $this->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonPath('data.total_projects', 0)
        ->assertJsonPath('data.total_tasks', 0)
        ->assertJsonPath('data.overdue_tasks', 0);
});

it('requires authentication', function () {
    app()['auth']->forgetGuards();

    $this->getJson('/api/dashboard')->assertUnauthorized();
});
