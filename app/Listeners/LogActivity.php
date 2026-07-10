<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogActivity
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
    public function handle(ProjectCreated $event): void
    {
        $modelType = class_basename($event->task ?? $event->project);
        $model     = $event->task ?? $event->project;

        ActivityLog::create([
            'tenant_id'  => $model->tenant_id,
            'user_id'    => $event->user->id,
            'action'     => 'created',
            'model_type' => strtolower($modelType),
            'model_id'   => $model->id,
            'created_at' => now(),
        ]);
    }
}
