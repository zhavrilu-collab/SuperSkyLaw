<?php

namespace App\Models;

use App\Enums\TrustDirection;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustMovement extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'direction',
        'amount_cents',
        'occurred_on',
        'counterparty',
        'purpose',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'direction' => TrustDirection::class,
            'occurred_on' => 'date',
        ];
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }
}
