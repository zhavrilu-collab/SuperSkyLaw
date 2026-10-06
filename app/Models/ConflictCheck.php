<?php

namespace App\Models;

use App\Enums\ConflictResult;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConflictCheck extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'result',
        'matches',
        'checked_by_user_id',
        'acknowledged_by_user_id',
        'acknowledged_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'result' => ConflictResult::class,
            'matches' => 'array',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by_user_id');
    }
}
