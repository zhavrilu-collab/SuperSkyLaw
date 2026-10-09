<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatterStage extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'name',
        'color',
        'started_on',
        'ended_on',
        'body',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MatterDocument::class, 'stage_id');
    }

    public function isOpen(): bool
    {
        return $this->ended_on === null;
    }
}
