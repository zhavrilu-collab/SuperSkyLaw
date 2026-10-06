<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffBand extends Model
{
    protected $fillable = [
        'tariff_version_id',
        'value_from_cents',
        'value_to_cents',
        'base_points',
        'threshold_cents',
        'step_cents',
        'step_points',
        'max_points',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(TariffVersion::class, 'tariff_version_id');
    }
}
