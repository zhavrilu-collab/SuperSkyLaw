<?php

namespace App\Models;

use App\Enums\OfficeKind;
use Illuminate\Database\Eloquent\Model;

class LawyerDirectoryEntry extends Model
{
    protected $fillable = [
        'source_key',
        'name',
        'office_kind',
        'address',
        'city',
        'phone',
        'search_normalized',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'office_kind' => OfficeKind::class,
            'synced_at' => 'datetime',
        ];
    }
}
