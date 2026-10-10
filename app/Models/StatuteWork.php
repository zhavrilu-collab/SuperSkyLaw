<?php

namespace App\Models;

use App\Enums\StatuteArea;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatuteWork extends Model
{
    protected $fillable = [
        'title',
        'title_key',
        'base_external_id',
        'area',
    ];

    protected function casts(): array
    {
        return [
            'area' => StatuteArea::class,
        ];
    }

    public function statutes(): HasMany
    {
        return $this->hasMany(Statute::class, 'work_id');
    }
}
