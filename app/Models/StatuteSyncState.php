<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatuteSyncState extends Model
{
    protected $fillable = [
        'source',
        'cursor',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'cursor' => 'array',
            'finished_at' => 'datetime',
        ];
    }
}
