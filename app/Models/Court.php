<?php

namespace App\Models;

use App\Enums\CourtType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    protected $fillable = [
        'name',
        'type',
        'city',
        'active',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'type' => CourtType::class,
            'active' => 'boolean',
        ];
    }

    public function matters(): HasMany
    {
        return $this->hasMany(Matter::class);
    }
}
