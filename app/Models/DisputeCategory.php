<?php

namespace App\Models;

use App\Enums\MatterKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisputeCategory extends Model
{
    protected $fillable = [
        'kind',
        'name',
        'hint',
        'sort',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'kind' => MatterKind::class,
            'active' => 'boolean',
        ];
    }

    public function matters(): HasMany
    {
        return $this->hasMany(Matter::class);
    }
}
