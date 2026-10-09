<?php

namespace App\Models;

use App\Enums\MatterKind;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeStageTemplate extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'kind',
        'name',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'kind' => MatterKind::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
