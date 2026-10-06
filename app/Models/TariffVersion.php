<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TariffVersion extends Model
{
    protected $fillable = [
        'code',
        'name',
        'citation',
        'point_value_cents',
        'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
        ];
    }

    public function bands(): HasMany
    {
        return $this->hasMany(TariffBand::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(TariffAction::class);
    }
}
