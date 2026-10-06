<?php

namespace App\Models;

use App\Enums\TimeEntryStatus;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'user_id',
        'description',
        'started_at',
        'ended_at',
        'minutes',
        'hourly_rate_cents',
        'status',
        'invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'status' => TimeEntryStatus::class,
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

    public function isRunning(): bool
    {
        return $this->started_at !== null && $this->ended_at === null;
    }

    public function valueCents(): int
    {
        $rate = $this->hourly_rate_cents ?? 0;

        return (int) round($this->minutes / 60 * $rate);
    }
}
