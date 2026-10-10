<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatuteWork extends Model
{
    protected $fillable = [
        'title',
        'title_key',
        'base_external_id',
    ];

    public function statutes(): HasMany
    {
        return $this->hasMany(Statute::class, 'work_id');
    }
}
