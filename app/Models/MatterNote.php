<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatterNote extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'stage_id',
        'user_id',
        'body',
        'noted_on',
    ];

    protected function casts(): array
    {
        return [
            'noted_on' => 'date',
        ];
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(MatterStage::class, 'stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
