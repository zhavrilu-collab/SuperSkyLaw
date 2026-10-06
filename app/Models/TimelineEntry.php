<?php

namespace App\Models;

use App\Enums\TimelineEntryType;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineEntry extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'type',
        'body',
        'occurred_at',
        'user_id',
        'visible_to_client',
    ];

    protected function casts(): array
    {
        return [
            'type' => TimelineEntryType::class,
            'occurred_at' => 'datetime',
            'visible_to_client' => 'boolean',
        ];
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
