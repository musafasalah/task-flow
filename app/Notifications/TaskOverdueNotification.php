<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Task Overdue: '.$this->task->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your task "'.$this->task->title.'" is now overdue.')
            ->line('Due date: '.$this->task->due_date?->toFormattedDateString())
            ->line('Priority: '.$this->task->priority->value)
            ->line('Please review it at your earliest convenience.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'project_id' => $this->task->project_id,
            'title' => $this->task->title,
            'due_date' => $this->task->due_date?->toDateString(),
            'message' => 'Task "'.$this->task->title.'" is overdue.',
        ];
    }
}
