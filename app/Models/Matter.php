<?php

namespace App\Models;

use App\Enums\BillingMethod;
use App\Enums\CourtEventType;
use App\Enums\MatterKind;
use App\Enums\MatterOutcome;
use App\Enums\MatterPhase;
use App\Enums\MatterStatus;
use App\Enums\OfficePosition;
use App\Enums\OrganizationRole;
use App\Enums\PartySide;
use App\Services\CroatianCalendar;
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
        'office_position',
        'outcome',
        'spnft_required',
        'court_id',
        'dispute_category_id',
        'court_name',
        'court_case_number',
        'case_mark',
        'case_number',
        'case_year',
        'filed_on',
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
            'office_position' => OfficePosition::class,
            'outcome' => MatterOutcome::class,
            'billing_method' => BillingMethod::class,
            'spnft_required' => 'boolean',
            'filed_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Matter $matter): void {
            if (blank($matter->court_case_number) && filled($matter->case_mark) && filled($matter->case_number) && filled($matter->case_year)) {
                $matter->court_case_number = $matter->case_mark.'-'.$matter->case_number.'/'.$matter->case_year;
            }
        });
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

    public function stages(): HasMany
    {
        return $this->hasMany(MatterStage::class)->orderBy('position');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(MatterNote::class)->latest();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function statutes(): BelongsToMany
    {
        return $this->belongsToMany(Statute::class, 'matter_statute')->withTimestamps();
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

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function disputeCategory(): BelongsTo
    {
        return $this->belongsTo(DisputeCategory::class);
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
        $number = $this->court_case_number;
        if (! $number && $this->case_mark) {
            $number = trim(implode('-', array_filter([$this->case_mark, $this->case_number, $this->case_year])));
        }

        $parts = array_filter([$this->court_name, $number]);

        return $parts === [] ? '—' : implode(' ', $parts);
    }

    public function clientLabel(): string
    {
        $parties = $this->relationLoaded('parties')
            ? $this->parties
            : $this->parties()->with('party')->get();

        return $parties
            ->filter(fn (MatterParty $party) => $party->side === PartySide::Client && filled($party->party?->name))
            ->map(fn (MatterParty $party) => $party->party->name)
            ->implode(', ');
    }

    public function courtLabel(): string
    {
        return $this->court?->name ?: (string) ($this->court_name ?? '');
    }

    public function phase(): MatterPhase
    {
        if ($this->status === MatterStatus::Archived) {
            return MatterPhase::Archived;
        }

        if ($this->status === MatterStatus::Paused) {
            return MatterPhase::Paused;
        }

        $events = $this->relationLoaded('courtEvents')
            ? $this->courtEvents
            : $this->courtEvents()->get();

        $today = now(CroatianCalendar::TIMEZONE)->startOfDay();
        $horizon = $today->copy()->addDays(7);
        $openDeadlines = $events->filter(fn (CourtEvent $event) => $event->completed_at === null && $this->isWritingDeadline($event));

        $dueToday = $openDeadlines->contains(function (CourtEvent $event) use ($today): bool {
            return $event->starts_at->timezone(CroatianCalendar::TIMEZONE)->startOfDay()->lte($today);
        });
        if ($dueToday) {
            return MatterPhase::DueToday;
        }

        $urgent = $openDeadlines->contains(function (CourtEvent $event) use ($horizon): bool {
            return $event->starts_at->timezone(CroatianCalendar::TIMEZONE)->startOfDay()->lte($horizon);
        });
        if ($urgent) {
            return MatterPhase::Urgent;
        }

        if ($openDeadlines->isNotEmpty()) {
            return MatterPhase::InTime;
        }

        $hearing = $events->contains(fn (CourtEvent $event) => $event->completed_at === null
            && in_array($event->type, [CourtEventType::Hearing, CourtEventType::Inspection], true)
            && $event->starts_at->greaterThan(now()));
        if ($hearing) {
            return MatterPhase::HearingSet;
        }

        $finishedDeadline = $events->contains(fn (CourtEvent $event) => $event->completed_at !== null && $this->isWritingDeadline($event));
        if ($finishedDeadline) {
            return MatterPhase::Waiting;
        }

        return MatterPhase::NewMatter;
    }

    private function isWritingDeadline(CourtEvent $event): bool
    {
        return $event->is_preclusive
            || $event->type->isDeadline()
            || in_array($event->origin, ['statutory', 'court_ordered'], true);
    }
}
