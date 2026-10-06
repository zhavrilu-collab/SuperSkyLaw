<?php

namespace App\Models;

use App\Enums\CourtEventType;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourtEvent extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'type',
        'title',
        'court_name',
        'starts_at',
        'ends_at',
        'responsible_user_id',
        'is_preclusive',
        'notes',
        'e_oglasna_url',
        'completed_at',
        'source',
        'external_uid',
    ];

    protected function casts(): array
    {
        return [
            'type' => CourtEventType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_preclusive' => 'boolean',
        ];
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(DeadlineReminder::class);
    }
}
