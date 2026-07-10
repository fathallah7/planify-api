<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Mail\TaskAssignedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendTaskNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TaskAssigned $event): void
    {
        $assignee = $event->task->assignee;

        if ($assignee) {
            Mail::to($assignee->email)
                ->queue(new TaskAssignedMail($event->task, $assignee));
        }
    }
}
