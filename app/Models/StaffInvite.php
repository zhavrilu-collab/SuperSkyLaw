<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StaffInvite extends Model
{
    protected $fillable = [
        'organization_id',
        'email',
        'role',
        'token',
        'invited_by_user_id',
        'accepted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public static function issue(
        Organization $organization,
        string $email,
        OrganizationRole $role,
        ?int $invitedByUserId,
        int $expiresDays = 7,
    ): self {
        return static::query()->create([
            'organization_id' => $organization->id,
            'email' => strtolower($email),
            'role' => $role,
            'token' => Str::random(48),
            'invited_by_user_id' => $invitedByUserId,
            'expires_at' => now()->addDays($expiresDays),
        ]);
    }

    public function isValid(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
