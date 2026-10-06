<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpnftCheck extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'item',
        'completed_by_user_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
