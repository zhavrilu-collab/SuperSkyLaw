<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TariffAction extends Model
{
    protected $fillable = [
        'tariff_version_id',
        'code',
        'label',
        'kind',
        'multiplier_percent',
        'fixed_points',
        'max_points',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(TariffVersion::class, 'tariff_version_id');
    }
}
