<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\DocumentKind;
use App\Enums\BillingMethod;
use App\Enums\ConflictResult;
use App\Enums\MatterKind;
use App\Enums\MatterOutcome;
use App\Enums\MatterPartyRole;
use App\Enums\MatterPhase;
use App\Enums\MatterStatus;
use App\Enums\OfficePosition;
use App\Enums\OrganizationRole;
use App\Enums\PartyKind;
use App\Enums\PartySide;
use App\Enums\TimelineEntryType;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\AuditLog;
use App\Models\ConflictCheck;
use App\Models\Court;
use App\Models\DisputeCategory;
use App\Models\Matter;
use App\Models\MatterNote;
use App\Models\MatterParty;
use App\Models\MatterStage;
use App\Models\OrganizationUser;
use App\Models\Party;
use App\Models\Statute;
use App\Models\TariffAction;
use App\Models\TimeEntry;
use App\Models\TimelineEntry;
use App\Rules\CourtCaseNumberRule;
use App\Rules\ValidOib;
use App\Services\ConflictCheckService;
use App\Services\MatterActivityFeed;
use App\Services\MatterLedger;
use App\Services\MatterStagePlan;
use App\Services\OrganizationRbacService;
use App\Services\StatutoryDeadlineCatalog;
use App\Services\TariffCatalog;
use App\Support\CourtCaseNumber;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MatterController extends Controller
{
    use ResolvesOffice;

    public function __construct(
        private readonly ConflictCheckService $conflicts,
        private readonly PlanFeatureService $plans,
        private readonly MatterStagePlan $stages,
        private readonly MatterActivityFeed $activityFeed,
        private readonly MatterLedger $ledger,
        private readonly OrganizationRbacService $access,
    ) {}

    public function index(Request $request, string $slug): View
    {
        $this->authorizePerm('matters.view');

        $sort = $request->string('sort')->toString();
        if (! in_array($sort, ['number', 'party', 'title', 'case_number', 'kind', 'dispute', 'court', 'status'], true)) {
            $sort = 'number';
        }
        $dir = $request->string('dir')->toString() === 'asc' ? 'asc' : 'desc';

        $filters = [
            'q' => trim($request->string('q')->toString()),
            'kind' => MatterKind::tryFrom($request->string('kind')->toString())?->value ?? '',
            'dispute' => ctype_digit($request->string('dispute')->toString()) ? $request->string('dispute')->toString() : '',
            'court' => $request->string('court')->toString(),
            'status' => MatterPhase::tryFrom($request->string('status')->toString())?->value ?? '',
        ];
        if (! ctype_digit($filters['court']) && ! str_starts_with($filters['court'], 'text:')) {
            $filters['court'] = '';
        }

        $matters = Matter::query()
            ->visibleTo($this->membership())
            ->with(['assignees', 'courtEvents', 'parties.party', 'court', 'disputeCategory'])
            ->get();

        $extraCourts = $matters
            ->filter(fn (Matter $matter) => $matter->court_id === null && filled($matter->court_name))
            ->map(fn (Matter $matter) => $matter->court_name)
            ->unique()
            ->sort()
            ->values();

        $matters = $this->filterMatters($matters, $filters);
        $matters = $this->sortMatters($matters, $sort, $dir);

        return view('organization.matters.index', [
            'matters' => $matters,
            'filters' => $filters,
            'sort' => $sort,
            'dir' => $dir,
            'extraCourts' => $extraCourts,
            ...$this->formCatalogs(),
        ]);
    }

    public function create(string $slug): View
    {
        $this->authorizePerm('matters.manage');

        return view('organization.matters.form', [
            'matter' => new Matter(['status' => MatterStatus::Active, 'billing_method' => BillingMethod::Hourly]),
            'lawyers' => $this->lawyers(),
            'conflict' => session('conflict'),
            ...$this->formCatalogs(),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $data = $this->validateMatter($request, true);
        $subjects = $this->subjectsFrom($data);
        $evaluation = $this->conflicts->evaluate($subjects);

        if ($evaluation['result'] !== ConflictResult::Clear && ! $request->boolean('acknowledge_conflict')) {
            return back()->withInput()->with('conflict', $evaluation);
        }

        if ($evaluation['result'] !== ConflictResult::Clear && $this->membership()->role !== OrganizationRole::Owner) {
            return back()->withInput()->with('conflict', $evaluation)->withErrors([
                'acknowledge_conflict' => 'Mogući ili tvrdi sukob može potvrditi samo partner.',
            ]);
        }

        $limit = $this->plans->limit($this->office(), 'matter_limit');
        if ($limit !== null && Matter::query()->count() >= $limit) {
            return back()->withErrors(['title' => 'Dosegnut je limit predmeta za trenutni plan.']);
        }

        $matter = DB::transaction(function () use ($data, $evaluation) {
            $matter = Matter::query()->create([
                'title' => $data['title'],
                'internal_number' => $this->nextInternalNumber(),
                'status' => MatterStatus::Active,
                'spnft_required' => in_array($data['kind'], config('spnft.kinds'), true),
                'created_by_user_id' => auth()->id(),
                ...$this->matterAttributes($data),
            ]);

            $assignees = array_unique(array_merge($data['assignee_ids'] ?? [], [(int) auth()->id()]));
            $matter->assignees()->sync($assignees);

            $this->attachSubject($matter, $data, 'client', PartySide::Client, MatterPartyRole::Client);
            if (filled($data['opposing_name'] ?? null)) {
                $this->attachSubject($matter, $data, 'opposing', PartySide::Opposing, MatterPartyRole::Defendant);
            }

            ConflictCheck::query()->create([
                'matter_id' => $matter->id,
                'result' => $evaluation['result'],
                'matches' => $evaluation['matches'],
                'checked_by_user_id' => auth()->id(),
                'acknowledged_by_user_id' => $evaluation['result'] === ConflictResult::Clear ? null : auth()->id(),
                'acknowledged_at' => $evaluation['result'] === ConflictResult::Clear ? null : now(),
                'note' => $data['conflict_note'] ?? null,
            ]);

            AuditLog::record(AuditAction::Create, $matter, 'Otvoren predmet '.$matter->internal_number);
            $this->stages->open($matter);

            return $matter;
        });

        return redirect()
            ->route('organization.matters.show', [$this->office()->slug, $matter->id])
            ->with('status', 'Predmet '.$matter->internal_number.' je otvoren.');
    }

    public function show(Request $request, string $slug, int $matter): View
    {
        $this->authorizePerm('matters.view');
        $model = $this->findVisibleMatter($matter);
        $model->load([
            'assignees',
            'parties.party',
            'conflictChecks.checker',
            'timelineEntries.user',
            'documents.uploader',
            'documents.stage',
            'stages.documents',
            'notes.user',
            'ethicalWalls.user',
            'spnftChecks',
            'limitationEstimate',
            'statutes',
            'courtEvents.responsible',
            'disputeCategory',
            'court',
            'creator',
        ]);
        AuditLog::record(AuditAction::View, $model, 'Pregled predmeta '.$model->internal_number);

        $tab = $request->string('tab')->toString();
        if (in_array($tab, ['biljeske', 'kronologija'], true)) {
            $tab = 'aktivnosti';
        }
        if (! in_array($tab, ['podaci', 'rokovi', 'dokumenti', 'aktivnosti', 'obracun'], true)) {
            $tab = 'podaci';
        }

        $userId = (int) auth()->id();
        $officeId = $this->office()->id;
        $seesMoney = $this->access->can($officeId, $userId, 'finance.view');
        $showLedger = $seesMoney || $this->access->can($officeId, $userId, 'time.view');
        if ($tab === 'obracun' && ! $showLedger) {
            abort(403, 'Nemate ovlasti za ovu radnju.');
        }

        $tariffActions = collect();
        if ($tab === 'obracun' && $this->access->can($officeId, $userId, 'finance.manage') && $this->plans->allows($this->office(), 'tariff_hok')) {
            $tariffActions = TariffAction::query()->where('tariff_version_id', app(TariffCatalog::class)->current()->id)->orderBy('id')->get();
        }

        return view('organization.matters.show', [
            'matter' => $model,
            'tab' => $tab,
            'showLedger' => $showLedger,
            'seesMoney' => $seesMoney,
            'canManageFinance' => $this->access->can($officeId, $userId, 'finance.manage'),
            'canManageTime' => $this->access->can($officeId, $userId, 'time.manage'),
            'canApproveTime' => $this->access->can($officeId, $userId, 'time.approve'),
            'requiresTimeApproval' => $this->plans->allows($this->office(), 'time_approval'),
            'tariffActions' => $tariffActions,
            'runningEntry' => $tab === 'obracun'
                ? TimeEntry::query()->where('user_id', $userId)->whereNull('ended_at')->whereNotNull('started_at')->first()
                : null,
            'activities' => $tab === 'aktivnosti' ? $this->activityFeed->for($model, $seesMoney) : collect(),
            'ledger' => $tab === 'obracun' ? $this->ledger->summarize($model) : null,
            'vrsta' => DocumentKind::tryFrom($request->string('vrsta')->toString())?->value ?? '',
            'stageShares' => $this->stages->shares($model->stages),
            'nextStageName' => $this->stages->suggestion($model),
            'lawyers' => $this->lawyers(),
            'parties' => Party::query()->orderBy('name')->get(),
            'members' => OrganizationUser::query()->with('user')->where('organization_id', $this->office()->id)->get(),
            'statuteMatches' => $this->statuteMatches($request, $tab),
            'deadlineRules' => app(StatutoryDeadlineCatalog::class)->forMatter($model),
            'deadlinePreview' => session('deadline_preview'),
            ...$this->formCatalogs(),
        ]);
    }

    public function storeStage(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($model, $data): void {
            $open = $model->stages()->whereNull('ended_on')->orderByDesc('position')->first();
            if ($open) {
                $open->update(['ended_on' => now()->toDateString()]);
            }

            $position = ((int) $model->stages()->max('position')) + 1;
            $model->stages()->create([
                'name' => $data['name'],
                'body' => $data['body'] ?? null,
                'color' => $this->stages->colorFor($position),
                'started_on' => now()->toDateString(),
                'position' => $position,
            ]);
        });

        AuditLog::record(AuditAction::Update, $model, 'Novi stadij predmeta '.$model->internal_number);

        return redirect()
            ->route('organization.matters.show', [$this->office()->slug, $model->id, 'tab' => 'podaci'])
            ->with('status', 'Stadij je otvoren.');
    }

    public function updateStage(Request $request, string $slug, int $matter, int $stage): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $record = MatterStage::query()->where('matter_id', $model->id)->findOrFail($stage);
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
        ]);
        $record->update(['body' => $data['body'] ?? null]);

        return redirect()
            ->route('organization.matters.show', [$this->office()->slug, $model->id, 'tab' => 'podaci'])
            ->with('status', 'Zapis stadija je spremljen.');
    }

    public function storeNote(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        MatterNote::query()->create([
            'matter_id' => $model->id,
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        return redirect()
            ->route('organization.matters.show', [$this->office()->slug, $model->id, 'tab' => 'aktivnosti'])
            ->with('status', 'Bilješka je spremljena.');
    }

    public function update(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $data = $this->validateMatter($request, false);

        $model->update([
            'title' => $data['title'],
            ...$this->matterAttributes($data),
        ]);

        if (isset($data['assignee_ids'])) {
            $model->assignees()->sync($data['assignee_ids']);
        }

        AuditLog::record(AuditAction::Update, $model, 'Izmjena predmeta '.$model->internal_number);

        return back()->with('status', 'Predmet je ažuriran.');
    }

    public function destroy(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.delete');
        $model = $this->findVisibleMatter($matter);
        $data = $request->validate([
            'outcome' => ['required', Rule::enum(MatterOutcome::class)],
        ]);
        $model->update([
            'status' => MatterStatus::Archived,
            'outcome' => $data['outcome'],
        ]);
        AuditLog::record(AuditAction::Update, $model, 'Arhiviranje predmeta '.$model->internal_number);

        return redirect()
            ->route('organization.matters.index', $this->office()->slug)
            ->with('status', 'Predmet je arhiviran. Spis ostaje u evidenciji.');
    }

    public function pause(string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        if ($model->status === MatterStatus::Archived) {
            return back();
        }

        $paused = $model->status !== MatterStatus::Paused;
        $model->update(['status' => $paused ? MatterStatus::Paused : MatterStatus::Active]);
        AuditLog::record(AuditAction::Update, $model, $paused ? 'Pauziranje predmeta '.$model->internal_number : 'Nastavak predmeta '.$model->internal_number);

        return back()->with('status', $paused ? 'Predmet je pauziran.' : 'Predmet je nastavljen.');
    }

    public function reopen(string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.delete');
        $model = $this->findVisibleMatter($matter);
        $model->update([
            'status' => MatterStatus::Active,
            'outcome' => null,
        ]);
        AuditLog::record(AuditAction::Update, $model, 'Povratak predmeta '.$model->internal_number.' u rad');

        return back()->with('status', 'Predmet je vraćen u rad.');
    }

    public function attachParty(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $data = $request->validate([
            'party_id' => ['required', 'integer'],
            'role' => ['required', Rule::enum(MatterPartyRole::class)],
            'side' => ['required', Rule::enum(PartySide::class)],
        ]);

        $party = Party::query()->findOrFail($data['party_id']);
        $evaluation = $this->conflicts->evaluate([[
            'name' => $party->name,
            'oib' => $party->oib,
            'side' => PartySide::from($data['side']),
        ]]);

        if ($evaluation['result'] !== ConflictResult::Clear && ! $request->boolean('acknowledge_conflict')) {
            return back()->with('conflict', $evaluation)->withErrors([
                'party_id' => 'Provjera sukoba traži potvrdu partnera prije dodavanja stranke.',
            ]);
        }

        if ($evaluation['result'] !== ConflictResult::Clear && $this->membership()->role !== OrganizationRole::Owner) {
            return back()->withErrors(['party_id' => 'Sukob može potvrditi samo partner.']);
        }

        MatterParty::query()->updateOrCreate(
            ['matter_id' => $model->id, 'party_id' => $party->id],
            ['role' => $data['role'], 'side' => $data['side']],
        );

        ConflictCheck::query()->create([
            'matter_id' => $model->id,
            'result' => $evaluation['result'],
            'matches' => $evaluation['matches'],
            'checked_by_user_id' => auth()->id(),
            'acknowledged_by_user_id' => $evaluation['result'] === ConflictResult::Clear ? null : auth()->id(),
            'acknowledged_at' => $evaluation['result'] === ConflictResult::Clear ? null : now(),
        ]);

        return back()->with('status', 'Stranka je povezana s predmetom.');
    }

    public function storeTimeline(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $data = $request->validate([
            'type' => ['required', Rule::enum(TimelineEntryType::class)],
            'body' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
            'visible_to_client' => ['nullable', 'boolean'],
        ]);

        TimelineEntry::query()->create([
            'matter_id' => $model->id,
            'type' => $data['type'],
            'body' => $data['body'],
            'occurred_at' => $data['occurred_at'],
            'user_id' => auth()->id(),
            'visible_to_client' => $request->boolean('visible_to_client'),
        ]);

        return redirect()
            ->route('organization.matters.show', [$this->office()->slug, $model->id, 'tab' => 'aktivnosti'])
            ->with('status', 'Zapis je dodan u kronologiju.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMatter(Request $request, bool $creating): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(MatterKind::class)],
            'office_position' => ['required', Rule::enum(OfficePosition::class)],
            'court_id' => ['nullable'],
            'court_name' => ['nullable', 'string', 'max:255'],
            'court_case_number' => ['nullable', 'string', 'max:40', new CourtCaseNumberRule],
            'dispute_category_id' => ['nullable', 'integer', Rule::exists('dispute_categories', 'id')->where('kind', (string) $request->input('kind'))],
            'dispute_value' => ['nullable', 'numeric', 'min:0'],
            'billing_method' => ['required', Rule::enum(BillingMethod::class)],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'flat_fee' => ['nullable', 'numeric', 'min:0'],
            'success_fee_note' => ['nullable', 'string', 'max:2000'],
            'filed_on' => ['nullable', 'date'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer'],
        ];

        if ($creating) {
            $rules = array_merge($rules, [
                'client_name' => ['required', 'string', 'max:255'],
                'client_oib' => ['nullable', 'string', new ValidOib],
                'client_kind' => ['required', Rule::enum(PartyKind::class)],
                'opposing_name' => ['nullable', 'string', 'max:255'],
                'opposing_oib' => ['nullable', 'string', new ValidOib],
                'conflict_note' => ['nullable', 'string', 'max:2000'],
                'acknowledge_conflict' => ['nullable', 'boolean'],
            ]);
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function matterAttributes(array $data): array
    {
        $case = CourtCaseNumber::parts($data['court_case_number'] ?? null) ?? [];
        $courtId = null;
        $courtName = null;
        $selectedCourt = $data['court_id'] ?? null;

        if ($selectedCourt && $selectedCourt !== 'other') {
            $court = Court::query()->whereKey($selectedCourt)->where('active', true)->first();
            if ($court === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'court_id' => 'Odabrani sud nije na popisu.',
                ]);
            }
            $courtId = $court->id;
            $courtName = $court->name;
        } elseif ($selectedCourt === 'other') {
            $courtName = $data['court_name'] ?? null;
        }

        return [
            'kind' => $data['kind'],
            'office_position' => $data['office_position'],
            'court_id' => $courtId,
            'court_name' => $courtName,
            'dispute_category_id' => $data['dispute_category_id'] ?? null,
            'dispute_value_cents' => $this->eurosToCents($data['dispute_value'] ?? null),
            'billing_method' => $data['billing_method'],
            'hourly_rate_cents' => $this->eurosToCents($data['hourly_rate'] ?? null),
            'flat_fee_cents' => $this->eurosToCents($data['flat_fee'] ?? null),
            'success_fee_note' => $data['success_fee_note'] ?? null,
            'filed_on' => $data['filed_on'] ?? null,
            ...$case,
        ];
    }

    /**
     * @return array{courts: \Illuminate\Support\Collection<int, Court>, categories: \Illuminate\Support\Collection<int, DisputeCategory>}
     */
    private function formCatalogs(): array
    {
        return [
            'courts' => Court::query()->where('active', true)->orderBy('sort')->get(),
            'categories' => DisputeCategory::query()->where('active', true)->orderBy('sort')->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{name: string, oib: ?string, side: PartySide}>
     */
    private function subjectsFrom(array $data): array
    {
        $subjects = [[
            'name' => $data['client_name'],
            'oib' => $data['client_oib'] ?? null,
            'side' => PartySide::Client,
        ]];

        if (filled($data['opposing_name'] ?? null)) {
            $subjects[] = [
                'name' => $data['opposing_name'],
                'oib' => $data['opposing_oib'] ?? null,
                'side' => PartySide::Opposing,
            ];
        }

        return $subjects;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function attachSubject(Matter $matter, array $data, string $prefix, PartySide $side, MatterPartyRole $role): void
    {
        $name = $data[$prefix.'_name'];
        $oib = $data[$prefix.'_oib'] ?? null;
        $oib = $oib !== '' ? $oib : null;

        $party = null;
        if ($oib !== null) {
            $party = Party::query()->where('oib', $oib)->first();
        }

        if ($party === null) {
            $party = Party::query()->create([
                'kind' => $prefix === 'client' ? $data['client_kind'] : PartyKind::Person,
                'name' => $name,
                'oib' => $oib,
            ]);
        }

        MatterParty::query()->create([
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'role' => $role,
            'side' => $side,
        ]);
    }

    private function nextInternalNumber(): string
    {
        $year = now()->year;
        $count = Matter::query()->where('internal_number', 'like', $year.'/%')->count() + 1;

        return sprintf('%d/%03d', $year, $count);
    }

    private function eurosToCents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }

    /**
     * @return \Illuminate\Support\Collection<int, OrganizationUser>
     */
    /**
     * @param  Collection<int, Matter>  $matters
     * @param  array{q: string, kind: string, dispute: string, court: string, status: string}  $filters
     * @return Collection<int, Matter>
     */
    private function filterMatters(Collection $matters, array $filters): Collection
    {
        return $matters->filter(function (Matter $matter) use ($filters): bool {
            if ($filters['kind'] !== '' && $matter->kind->value !== $filters['kind']) {
                return false;
            }
            if ($filters['dispute'] !== '' && (string) $matter->dispute_category_id !== $filters['dispute']) {
                return false;
            }
            if ($filters['status'] !== '' && $matter->phase()->value !== $filters['status']) {
                return false;
            }
            if (str_starts_with($filters['court'], 'text:')) {
                $name = substr($filters['court'], 5);
                if ($matter->court_id !== null || $matter->court_name !== $name) {
                    return false;
                }
            } elseif ($filters['court'] !== '' && (string) $matter->court_id !== $filters['court']) {
                return false;
            }
            if ($filters['q'] === '') {
                return true;
            }

            $haystack = mb_strtolower(implode(' ', array_filter([
                $matter->internal_number,
                $matter->clientLabel(),
                $matter->title,
                $matter->court_case_number,
                $matter->kind->label(),
                $matter->disputeCategory?->name,
                $matter->courtLabel(),
                $matter->phase()->label(),
                ...$matter->parties->map(fn (MatterParty $party) => $party->party?->name)->all(),
            ])));

            return str_contains($haystack, mb_strtolower($filters['q']));
        })->values();
    }

    /**
     * @param  Collection<int, Matter>  $matters
     * @return Collection<int, Matter>
     */
    private function sortMatters(Collection $matters, string $sort, string $dir): Collection
    {
        $collator = class_exists(\Collator::class) ? new \Collator('hr') : null;

        return $matters->sort(function (Matter $left, Matter $right) use ($sort, $dir, $collator): int {
            $a = $this->sortValue($left, $sort);
            $b = $this->sortValue($right, $sort);

            if ($a === '' || $b === '') {
                if ($a === $b) {
                    return $left->id <=> $right->id;
                }

                return $a === '' ? 1 : -1;
            }

            $cmp = $collator ? $collator->compare($a, $b) : strcasecmp($a, $b);
            if ($cmp === 0) {
                $cmp = $left->id <=> $right->id;
            }

            return $dir === 'desc' ? -$cmp : $cmp;
        })->values();
    }

    private function sortValue(Matter $matter, string $sort): string
    {
        return match ($sort) {
            'party' => $matter->clientLabel(),
            'title' => (string) $matter->title,
            'case_number' => (string) ($matter->court_case_number ?? ''),
            'kind' => $matter->kind->label(),
            'dispute' => (string) ($matter->disputeCategory?->name ?? ''),
            'court' => $matter->courtLabel(),
            'status' => $matter->phase()->label(),
            default => (string) $matter->internal_number,
        };
    }

    private function lawyers()
    {
        return OrganizationUser::query()
            ->with('user')
            ->where('organization_id', $this->office()->id)
            ->whereIn('role', [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Trainee])
            ->get();
    }

    private function statuteMatches(Request $request, string $tab)
    {
        $term = trim($request->string('zakon')->toString());
        if ($tab !== 'podaci' || $term === '') {
            return collect();
        }

        $like = '%'.$term.'%';

        return Statute::query()
            ->where(function ($query) use ($like) {
                $query->where('title', 'like', $like)->orWhere('citation', 'like', $like);
            })
            ->orderBy('title')
            ->limit(8)
            ->get();
    }
}
