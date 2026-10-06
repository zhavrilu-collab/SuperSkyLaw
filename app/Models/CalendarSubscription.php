<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class CalendarSubscription extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'feed_url',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }
}
