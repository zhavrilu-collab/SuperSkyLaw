<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadlineReminder extends Model
{
    protected $fillable = [
        'court_event_id',
        'offset_minutes',
        'email_sent_at',
        'in_app_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'email_sent_at' => 'datetime',
            'in_app_sent_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(CourtEvent::class, 'court_event_id');
    }

    public function label(): string
    {
        return match ($this->offset_minutes) {
            10080 => '7 dana',
            1440 => '1 dan',
            60 => '1 sat',
            default => $this->offset_minutes.' min',
        };
    }
}
