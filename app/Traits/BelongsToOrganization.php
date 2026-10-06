<?php

namespace App\Traits;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder): void {
            $organizationId = static::currentOrganizationId();

            if ($organizationId !== null) {
                $builder->where(
                    $builder->getModel()->getTable().'.organization_id',
                    $organizationId
                );
            }
        });

        static::creating(function (Model $model): void {
            if (! $model->getAttribute('organization_id')) {
                $organizationId = static::currentOrganizationId();

                if ($organizationId !== null) {
                    $model->organization_id = $organizationId;
                }
            }
        });
    }

    public static function currentOrganizationId(): ?int
    {
        if (! app()->bound('currentOrganization')) {
            return null;
        }

        $organization = app('currentOrganization');

        return $organization instanceof Organization ? $organization->id : null;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
