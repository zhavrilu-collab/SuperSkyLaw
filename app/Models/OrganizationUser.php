<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationUser extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool
    {
        return $this->role === OrganizationRole::Owner;
    }
}
