<?php

namespace App\Models;

use App\Enums\SmsStatus;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'party_id',
        'court_event_id',
        'phone',
        'body',
        'cost_cents',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => SmsStatus::class,
        ];
    }
}
