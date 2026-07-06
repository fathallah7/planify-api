<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProjectService $projectService
    ) {}

    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Project::class);

        $projects = $this->projectService->getAllProjects();
        return $this->success(data: ProjectResource::collection($projects), message: 'Projects retrieved successfully');
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        Gate::authorize('create', Project::class);

        $project = $this->projectService->createProject(
            $request->validated(),
            $request->user()
        );

        return $this->success(data: new ProjectResource($project), message: 'Project created successfully', status: 201);
    }

    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return $this->success(data: new ProjectResource($project), message: 'Project retrieved successfully');
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $updatedProject = $this->projectService->updateProject($project, $request->validated());
        return $this->success(data: new ProjectResource($updatedProject), message: 'Project updated successfully');
    }

    public function destroy(Project $project): JsonResponse
    {
        Gate::authorize('delete', $project);

        $this->projectService->deleteProject($project);
        return $this->success(message: 'Project deleted successfully');
    }
}
