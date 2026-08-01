<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'description', 'priority', 'status', 'due_date'])]
class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'priority' => TaskPriority::Medium->value,
        'status' => TaskStatus::Todo->value,
    ];

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'due_date' => 'date',
            'overdue_notified_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isOverdue(): bool
    {
        return $this->status !== TaskStatus::Done
            && $this->due_date !== null
            && $this->due_date->lt(today());
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->where('status', '!=', TaskStatus::Done);
    }

    public function scopeNeedingOverdueNotification(Builder $query): void
    {
        $query->overdue()->whereNull('overdue_notified_at');
    }
}
