<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\IndexTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function index(IndexTaskRequest $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $tasks = $this->taskService->listForProject(
            $project,
            $request->filters(),
            $request->integer('per_page', 15),
        );

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $task = $this->taskService->create($project, $request->validated());

        return TaskResource::make($task)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Project $project, Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return TaskResource::make($task);
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task = $this->taskService->update($task, $request->validated());

        return TaskResource::make($task);
    }

    public function destroy(Project $project, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->taskService->delete($task);

        return response()->json(status: 204);
    }
}
