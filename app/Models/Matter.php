<?php

namespace App\Models;

use App\Enums\BillingMethod;
use App\Enums\MatterKind;
use App\Enums\MatterStatus;
use App\Enums\OrganizationRole;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matter extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'title',
        'internal_number',
        'kind',
        'status',
        'spnft_required',
        'court_name',
        'case_mark',
        'case_number',
        'case_year',
        'dispute_value_cents',
        'billing_method',
        'hourly_rate_cents',
        'flat_fee_cents',
        'success_fee_note',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'kind' => MatterKind::class,
            'status' => MatterStatus::class,
            'billing_method' => BillingMethod::class,
            'spnft_required' => 'boolean',
        ];
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function parties(): HasMany
    {
        return $this->hasMany(MatterParty::class);
    }

    public function conflictChecks(): HasMany
    {
        return $this->hasMany(ConflictCheck::class);
    }

    public function timelineEntries(): HasMany
    {
        return $this->hasMany(TimelineEntry::class);
    }

    public function courtEvents(): HasMany
    {
        return $this->hasMany(CourtEvent::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MatterDocument::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function ethicalWalls(): HasMany
    {
        return $this->hasMany(EthicalWall::class);
    }

    public function spnftChecks(): HasMany
    {
        return $this->hasMany(SpnftCheck::class);
    }

    public function limitationEstimate(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LimitationEstimate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeVisibleTo(Builder $query, OrganizationUser $membership): Builder
    {
        $query->whereDoesntHave('ethicalWalls', fn (Builder $walls) => $walls->where('user_id', $membership->user_id));

        if (in_array($membership->role, [OrganizationRole::Owner, OrganizationRole::Secretary], true)) {
            return $query;
        }

        return $query->whereHas('assignees', fn (Builder $assignees) => $assignees->where('users.id', $membership->user_id));
    }

    public function courtReference(): string
    {
        $parts = array_filter([
            $this->court_name,
            trim(implode('-', array_filter([$this->case_mark, $this->case_number, $this->case_year]))),
        ]);

        return $parts === [] ? '—' : implode(' ', $parts);
    }
}
