<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'due_date'    => $this->due_date?->toIso8601String(),
            'assigned_to' => $this->assigned_to,
            'created_by'  => $this->created_by,
            'project_id'  => $this->project_id,
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
