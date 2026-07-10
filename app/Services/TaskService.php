<?php

namespace App\Services;

use App\Events\TaskAssigned;
use App\Events\TaskCreated;
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

    $task = Task::create($data);

    event(new TaskCreated($task, $user));
    event(new TaskAssigned($task, $user));

    return $task;
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

    if (isset($data['assigned_to']) && $data['assigned_to'] !== $task->assigned_to) {
      event(new TaskAssigned($task, $user));
    }

    return $task;
  }

  public function deleteTask(Task $task): bool
  {
    return $task->delete();
  }
}
