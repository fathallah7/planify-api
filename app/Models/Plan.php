<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'price', 'billing_cycle', 'max_projects', 'max_members'])]
class Plan extends Model
{
    use HasUuids;

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}
