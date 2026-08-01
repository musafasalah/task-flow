<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

it('queues an overdue notification to the task owner', function () {
    Notification::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $task = Task::factory()->for($project)->create([
        'due_date' => now()->subDays(2),
        'status' => TaskStatus::Todo,
    ]);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Notification::assertSentTo(
        $user,
        TaskOverdueNotification::class,
        fn (TaskOverdueNotification $notification) => $notification->task->is($task),
    );

    expect($task->fresh()->overdue_notified_at)->not->toBeNull();
});

it('uses a queued notification', function () {
    expect(is_subclass_of(TaskOverdueNotification::class, ShouldQueue::class))->toBeTrue();
});

it('does not notify for tasks that are not overdue', function () {
    Notification::fake();

    $project = Project::factory()->create();
    Task::factory()->for($project)->create([
        'due_date' => now()->addWeek(),
        'status' => TaskStatus::Todo,
    ]);
    Task::factory()->for($project)->create([
        'due_date' => now()->subDays(2),
        'status' => TaskStatus::Done,
    ]);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Notification::assertNothingSent();
});

it('does not notify the same task twice', function () {
    Notification::fake();

    $project = Project::factory()->create();
    Task::factory()->for($project)->create([
        'due_date' => now()->subDays(2),
        'status' => TaskStatus::Todo,
        'overdue_notified_at' => now(),
    ]);

    $this->artisan('tasks:notify-overdue')->assertSuccessful();

    Notification::assertNothingSent();
});

it('resets the notified flag when a task is no longer overdue', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $task = Task::factory()->for($project)->create([
        'due_date' => now()->subDays(2),
        'status' => TaskStatus::Todo,
        'overdue_notified_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $this->putJson("/api/projects/{$project->id}/tasks/{$task->id}", [
        'status' => 'done',
    ])->assertOk();

    expect($task->fresh()->overdue_notified_at)->toBeNull();
});
