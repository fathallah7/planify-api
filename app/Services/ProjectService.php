<?php

namespace App\Services;

use App\Events\ProjectCreated;
use App\Exceptions\BusinessException;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ProjectService
{
  public function getAllProjects(): Collection
  {
    return Project::latest()->get();
  }

  public function createProject(array $data, User $user): Project
  {
    $tenant = $user->tenant;
    $projectsCount = $tenant->projects()->count();

    if ($tenant->plan && $tenant->plan->max_projects !== null && $projectsCount >= $tenant->plan->max_projects) {
      throw new BusinessException('You have reached the maximum number of projects allowed for your plan.');
    }

    $data['created_by'] = $user->id;

    $project = Project::create($data);

    event(new ProjectCreated($project, $user));

    return $project;
  }

  public function updateProject(Project $project, array $data): Project
  {
    $project->update($data);
    return $project;
  }

  public function deleteProject(Project $project): bool
  {
    return $project->delete();
  }
}
