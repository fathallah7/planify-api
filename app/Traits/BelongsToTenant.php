<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
  /**
   * Boot the trait to apply the scope and auto-assign tenant_id.
   */
  protected static function bootBelongsToTenant(): void
  {
    static::addGlobalScope(new TenantScope);

    static::creating(function ($model) {
      if (app()->has('tenant_id') && empty($model->tenant_id)) {
        $model->tenant_id = app('tenant_id');
      }
    });
  }

  /**
   * Tenant Relationship
   */
  public function tenant(): BelongsTo
  {
    return $this->belongsTo(Tenant::class);
  }
}
