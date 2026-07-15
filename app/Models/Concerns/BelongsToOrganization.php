<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes every query on the model to the authenticated user's current
 * organization, and stamps organization_id on newly created records.
 * This is the single enforcement point for tenant isolation.
 */
trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            $organizationId = auth()->user()?->current_organization_id;

            if ($organizationId !== null) {
                $builder->where(
                    $builder->getModel()->qualifyColumn('organization_id'),
                    $organizationId
                );
            }
        });

        static::creating(function (Model $model) {
            if ($model->getAttribute('organization_id') === null) {
                $model->setAttribute(
                    'organization_id',
                    auth()->user()?->current_organization_id
                );
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
