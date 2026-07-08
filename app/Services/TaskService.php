<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
  public function getProjectTasks(Project $project): Collection
  {
    return $project->tasks()->latest()->get();
  }

  public function createTask(array $data, Project $project, User $user): Task
  {
    if (isset($data['assigned_to'])) {
      $assignee = User::find($data['assigned_to']);
      if (!$assignee || $assignee->tenant_id !== $user->tenant_id) {
        throw new BusinessException('Assigned user does not belong to your organization.');
      }
    }

    $data['created_by']  = $user->id;
    $data['project_id']  = $project->id;

    return Task::create($data);
  }

  public function updateTask(Task $task, array $data, User $user): Task
  {
    if (isset($data['assigned_to'])) {
      $assignee = User::find($data['assigned_to']);
      if (!$assignee || $assignee->tenant_id !== $user->tenant_id) {
        throw new BusinessException('Assigned user does not belong to your organization.');
      }
    }

    $task->update($data);
    return $task;
  }

  public function deleteTask(Task $task): bool
  {
    return $task->delete();
  }
}
