<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatterStatute extends Model
{
    use BelongsToOrganization;

    protected $table = 'matter_statute';

    protected $fillable = [
        'organization_id',
        'matter_id',
        'statute_id',
        'created_by_user_id',
    ];

    public function statute(): BelongsTo
    {
        return $this->belongsTo(Statute::class);
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }
}
