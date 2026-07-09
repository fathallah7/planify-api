<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource['token'],
            'user'  => [
                'id'    => $this->resource['user']->id,
                'name'  => $this->resource['user']->name,
                'email' => $this->resource['user']->email,
                'role'  => $this->resource['user']->role,
            ],
        ];
    }
}
