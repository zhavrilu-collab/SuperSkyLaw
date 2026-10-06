<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ClientAccount extends Authenticatable
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'party_id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
