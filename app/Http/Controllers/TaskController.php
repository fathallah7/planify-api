<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TaskService $taskService
    ) {}

    public function index(Project $project): JsonResponse
    {
        Gate::authorize('viewAny', $project);

        $tasks = $this->taskService->getProjectTasks($project);
        return $this->success(data: TaskResource::collection($tasks), message: 'Tasks retrieved successfully');
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('create', $project);

        $task = $this->taskService->createTask(
            $request->validated(),
            $project,
            $request->user()
        );

        return $this->success(data: new TaskResource($task), message: 'Task created successfully', status: 201);
    }

    public function show(Project $project, Task $task): JsonResponse
    {
        Gate::authorize('view', $project);

        return $this->success(data: new TaskResource($task), message: 'Task retrieved successfully');
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        Gate::authorize('update', $project);

        $updatedTask = $this->taskService->updateTask($task, $request->validated(), $request->user());
        return $this->success(data: new TaskResource($updatedTask), message: 'Task updated successfully');
    }

    public function destroy(Project $project, Task $task): JsonResponse
    {
        Gate::authorize('delete', $project);

        $this->taskService->deleteTask($task);
        return $this->success(message: 'Task deleted successfully');
    }
}
