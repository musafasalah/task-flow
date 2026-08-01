<?php

namespace App\Console\Commands;

use App\Notifications\TaskOverdueNotification;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tasks:notify-overdue')]
#[Description('Queue overdue notifications for tasks that have just become overdue')]
class NotifyOverdueTasks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TaskRepositoryInterface $tasks): int
    {
        $overdueTasks = $tasks->overdueNeedingNotification();

        foreach ($overdueTasks as $task) {
            $task->project->user->notify(new TaskOverdueNotification($task));
            $tasks->markAsNotified($task);
        }

        $this->info("Queued overdue notifications for {$overdueTasks->count()} task(s).");

        return self::SUCCESS;
    }
}
