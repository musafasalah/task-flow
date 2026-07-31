<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->for($this->user)->create();
    Sanctum::actingAs($this->user);
});

it('lists tasks for a project', function () {
    Task::factory(3)->for($this->project)->create();

    $this->getJson("/api/projects/{$this->project->id}/tasks")
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'title', 'priority', 'status', 'is_overdue']], 'meta']);
});

it('filters tasks by status', function () {
    Task::factory(2)->for($this->project)->create(['status' => TaskStatus::Done]);
    Task::factory(3)->for($this->project)->create(['status' => TaskStatus::Todo]);

    $this->getJson("/api/projects/{$this->project->id}/tasks?status=done")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters tasks by priority', function () {
    Task::factory(1)->for($this->project)->create(['priority' => TaskPriority::High]);
    Task::factory(4)->for($this->project)->create(['priority' => TaskPriority::Low]);

    $this->getJson("/api/projects/{$this->project->id}/tasks?priority=high")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('searches tasks by title', function () {
    Task::factory()->for($this->project)->create(['title' => 'Deploy to production']);
    Task::factory()->for($this->project)->create(['title' => 'Write documentation']);

    $this->getJson("/api/projects/{$this->project->id}/tasks?search=deploy")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Deploy to production');
});

it('rejects an invalid status filter', function () {
    $this->getJson("/api/projects/{$this->project->id}/tasks?status=invalid")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('creates a task within a project', function () {
    $response = $this->postJson("/api/projects/{$this->project->id}/tasks", [
        'title' => 'New Task',
        'priority' => 'high',
        'status' => 'todo',
        'due_date' => '2026-12-31',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'New Task')
        ->assertJsonPath('data.priority', 'high');

    $this->assertDatabaseHas('tasks', [
        'title' => 'New Task',
        'project_id' => $this->project->id,
    ]);
});

it('defaults new tasks to medium priority and todo status', function () {
    $this->postJson("/api/projects/{$this->project->id}/tasks", ['title' => 'Bare Task'])
        ->assertCreated()
        ->assertJsonPath('data.priority', TaskPriority::Medium->value)
        ->assertJsonPath('data.status', TaskStatus::Todo->value);
});

it('validates required title when creating a task', function () {
    $this->postJson("/api/projects/{$this->project->id}/tasks", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

it('marks a past-due unfinished task as overdue', function () {
    $task = Task::factory()->for($this->project)->create([
        'due_date' => now()->subDays(3),
        'status' => TaskStatus::Todo,
    ]);

    $this->getJson("/api/projects/{$this->project->id}/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.is_overdue', true);
});

it('updates a task', function () {
    $task = Task::factory()->for($this->project)->create();

    $this->putJson("/api/projects/{$this->project->id}/tasks/{$task->id}", [
        'title' => 'Updated',
        'status' => 'done',
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated')
        ->assertJsonPath('data.status', 'done');
});

it('soft deletes a task', function () {
    $task = Task::factory()->for($this->project)->create();

    $this->deleteJson("/api/projects/{$this->project->id}/tasks/{$task->id}")->assertNoContent();

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

it('forbids accessing tasks in another user\'s project', function () {
    $otherProject = Project::factory()->create();
    $task = Task::factory()->for($otherProject)->create();

    $this->getJson("/api/projects/{$otherProject->id}/tasks")->assertForbidden();
    $this->getJson("/api/projects/{$otherProject->id}/tasks/{$task->id}")->assertForbidden();
});

it('returns 404 when the task does not belong to the given project', function () {
    $otherProject = Project::factory()->for($this->user)->create();
    $task = Task::factory()->for($otherProject)->create();

    $this->getJson("/api/projects/{$this->project->id}/tasks/{$task->id}")->assertNotFound();
});
