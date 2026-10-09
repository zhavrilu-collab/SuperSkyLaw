<?php

namespace App\Models;

use App\Enums\FeeAudience;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffCharge extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'tariff_version_id',
        'tariff_action_id',
        'audience',
        'description',
        'points',
        'amount_cents',
        'invoice_id',
        'court_event_id',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'audience' => FeeAudience::class,
        ];
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(TariffAction::class, 'tariff_action_id');
    }
}
