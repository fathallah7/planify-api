<!DOCTYPE html>
<html>

<body>
  <h1>You've been assigned to a task</h1>
  <p>Task: {{ $task->title }}</p>
  <p>Priority: {{ $task->priority }}</p>
  <p>Due Date: {{ $task->due_date?->format('d/m/Y') ?? 'No due date' }}</p>
</body>

</html>
