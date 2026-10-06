<?php

namespace App\Models;

use App\Enums\MatterPartyRole;
use App\Enums\PartySide;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatterParty extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'matter_id',
        'party_id',
        'role',
        'side',
    ];

    protected function casts(): array
    {
        return [
            'role' => MatterPartyRole::class,
            'side' => PartySide::class,
        ];
    }

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
