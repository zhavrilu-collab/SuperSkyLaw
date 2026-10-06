<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\BillingMethod;
use App\Enums\ConflictResult;
use App\Enums\MatterKind;
use App\Enums\MatterPartyRole;
use App\Enums\MatterStatus;
use App\Enums\OrganizationRole;
use App\Enums\PartyKind;
use App\Enums\PartySide;
use App\Enums\TimelineEntryType;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\AuditLog;
use App\Models\ConflictCheck;
use App\Models\Matter;
use App\Models\MatterParty;
use App\Models\OrganizationUser;
use App\Models\Party;
use App\Models\TimelineEntry;
use App\Rules\ValidOib;
use App\Services\ConflictCheckService;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MatterController extends Controller
{
    use ResolvesOffice;

    public function __construct(
        private readonly ConflictCheckService $conflicts,
        private readonly PlanFeatureService $plans,
    ) {}

    public function index(string $slug): View
    {
        $this->authorizePerm('matters.view');

        $matters = Matter::query()
            ->visibleTo($this->membership())
            ->with('assignees')
            ->orderByDesc('id')
            ->get();

        return view('organization.matters.index', ['matters' => $matters]);
    }

    public function create(string $slug): View
    {
        $this->authorizePerm('matters.manage');

        return view('organization.matters.form', [
            'matter' => new Matter(['status' => MatterStatus::Active, 'billing_method' => BillingMethod::Hourly]),
            'lawyers' => $this->lawyers(),
            'conflict' => session('conflict'),
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
                'kind' => $data['kind'],
                'status' => $data['status'],
                'court_name' => $data['court_name'] ?? null,
                'case_mark' => $data['case_mark'] ?? null,
                'case_number' => $data['case_number'] ?? null,
                'case_year' => $data['case_year'] ?? null,
                'dispute_value_cents' => $this->eurosToCents($data['dispute_value'] ?? null),
                'billing_method' => $data['billing_method'],
                'hourly_rate_cents' => $this->eurosToCents($data['hourly_rate'] ?? null),
                'flat_fee_cents' => $this->eurosToCents($data['flat_fee'] ?? null),
                'success_fee_note' => $data['success_fee_note'] ?? null,
                'spnft_required' => in_array($data['kind'], config('spnft.kinds'), true),
                'created_by_user_id' => auth()->id(),
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

            return $matter;
        });

        return redirect()
            ->route('organization.matters.show', [$this->office()->slug, $matter->id])
            ->with('status', 'Predmet '.$matter->internal_number.' je otvoren.');
    }

    public function show(string $slug, int $matter): View
    {
        $this->authorizePerm('matters.view');
        $model = $this->findVisibleMatter($matter);
        $model->load(['assignees', 'parties.party', 'conflictChecks.checker', 'timelineEntries.user', 'documents', 'ethicalWalls.user', 'spnftChecks', 'limitationEstimate']);
        AuditLog::record(AuditAction::View, $model, 'Pregled predmeta '.$model->internal_number);

        return view('organization.matters.show', [
            'matter' => $model,
            'lawyers' => $this->lawyers(),
            'parties' => Party::query()->orderBy('name')->get(),
            'members' => OrganizationUser::query()->with('user')->where('organization_id', $this->office()->id)->get(),
        ]);
    }

    public function update(Request $request, string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $model = $this->findVisibleMatter($matter);
        $data = $this->validateMatter($request, false);

        $model->update([
            'title' => $data['title'],
            'kind' => $data['kind'],
            'status' => $data['status'],
            'court_name' => $data['court_name'] ?? null,
            'case_mark' => $data['case_mark'] ?? null,
            'case_number' => $data['case_number'] ?? null,
            'case_year' => $data['case_year'] ?? null,
            'dispute_value_cents' => $this->eurosToCents($data['dispute_value'] ?? null),
            'billing_method' => $data['billing_method'],
            'hourly_rate_cents' => $this->eurosToCents($data['hourly_rate'] ?? null),
            'flat_fee_cents' => $this->eurosToCents($data['flat_fee'] ?? null),
            'success_fee_note' => $data['success_fee_note'] ?? null,
        ]);

        if (isset($data['assignee_ids'])) {
            $model->assignees()->sync($data['assignee_ids']);
        }

        AuditLog::record(AuditAction::Update, $model, 'Izmjena predmeta '.$model->internal_number);

        return back()->with('status', 'Predmet je ažuriran.');
    }

    public function destroy(string $slug, int $matter): RedirectResponse
    {
        $this->authorizePerm('matters.delete');
        $model = $this->findVisibleMatter($matter);
        $model->update(['status' => MatterStatus::Archived]);
        AuditLog::record(AuditAction::Update, $model, 'Arhiviranje predmeta '.$model->internal_number);

        return redirect()
            ->route('organization.matters.index', $this->office()->slug)
            ->with('status', 'Predmet je arhiviran. Spis ostaje u evidenciji.');
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

        return back()->with('status', 'Zapis je dodan u kronologiju.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMatter(Request $request, bool $creating): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(MatterKind::class)],
            'status' => ['required', Rule::enum(MatterStatus::class)],
            'court_name' => ['nullable', 'string', 'max:255'],
            'case_mark' => ['nullable', 'string', 'max:20'],
            'case_number' => ['nullable', 'string', 'max:30'],
            'case_year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'dispute_value' => ['nullable', 'numeric', 'min:0'],
            'billing_method' => ['required', Rule::enum(BillingMethod::class)],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'flat_fee' => ['nullable', 'numeric', 'min:0'],
            'success_fee_note' => ['nullable', 'string', 'max:2000'],
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
    private function lawyers()
    {
        return OrganizationUser::query()
            ->with('user')
            ->where('organization_id', $this->office()->id)
            ->whereIn('role', [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Trainee])
            ->get();
    }
}
