<?php

namespace App\Http\Controllers;

use App\Enums\PartyKind;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\Invoice;
use App\Models\Matter;
use App\Models\MatterDocument;
use App\Models\Party;
use App\Rules\ValidOib;
use App\Services\OrganizationRbacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartyController extends Controller
{
    use ResolvesOffice;

    public function index(Request $request, string $slug): View
    {
        $this->authorizePerm('parties.view');

        $sort = $request->string('sort')->toString();
        if (! in_array($sort, ['name', 'kind', 'oib', 'mbs', 'city'], true)) {
            $sort = 'name';
        }
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';
        $filters = [
            'q' => trim($request->string('q')->toString()),
            'kind' => PartyKind::tryFrom($request->string('kind')->toString())?->value ?? '',
            'city' => trim($request->string('city')->toString()),
        ];

        $parties = Party::query()->get();
        $cities = $parties->pluck('city')->filter(fn ($city) => filled($city))->unique()->sort()->values();
        $parties = $this->sortParties($this->filterParties($parties, $filters), $sort, $dir);

        return view('organization.parties.index', [
            'parties' => $parties,
            'filters' => $filters,
            'sort' => $sort,
            'dir' => $dir,
            'cities' => $cities,
        ]);
    }

    public function show(Request $request, string $slug, int $party): View
    {
        $this->authorizePerm('parties.view');
        $model = Party::query()->findOrFail($party);
        $tab = $request->string('tab')->toString();
        if (! in_array($tab, ['podaci', 'dokumenti', 'predmeti', 'racuni'], true)) {
            $tab = 'podaci';
        }

        $matters = Matter::query()
            ->visibleTo($this->membership())
            ->whereHas('parties', fn ($query) => $query->where('party_id', $model->id))
            ->with(['courtEvents', 'parties'])
            ->orderByDesc('id')
            ->get();

        $documents = MatterDocument::query()
            ->whereIn('matter_id', $matters->pluck('id'))
            ->with(['matter', 'uploader'])
            ->orderByDesc('id')
            ->get();

        $canFinance = app(OrganizationRbacService::class)->can($this->office()->id, (int) auth()->id(), 'finance.view');
        $invoices = $canFinance
            ? Invoice::query()->where('party_id', $model->id)->with('matter')->orderByDesc('issue_date')->get()
            : collect();

        return view('organization.parties.show', [
            'party' => $model,
            'tab' => $tab,
            'matters' => $matters,
            'documents' => $documents,
            'invoices' => $invoices,
            'canFinance' => $canFinance,
            'canManage' => app(OrganizationRbacService::class)->can($this->office()->id, (int) auth()->id(), 'parties.manage'),
        ]);
    }

    public function create(string $slug): View
    {
        $this->authorizePerm('parties.manage');

        return view('organization.parties.form', ['party' => new Party]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('parties.manage');
        $party = Party::query()->create($this->validated($request));

        return redirect()
            ->route('organization.parties.show', [$this->office()->slug, $party->id, 'tab' => 'podaci'])
            ->with('status', 'Stranka '.$party->name.' je spremljena.');
    }

    public function edit(string $slug, int $party): RedirectResponse
    {
        $this->authorizePerm('parties.view');

        return redirect()->route('organization.parties.show', [$this->office()->slug, $party, 'tab' => 'podaci']);
    }

    public function update(Request $request, string $slug, int $party): RedirectResponse
    {
        $this->authorizePerm('parties.manage');
        $model = Party::query()->findOrFail($party);
        $model->update($this->validated($request, $model->id));

        return redirect()
            ->route('organization.parties.show', [$this->office()->slug, $model->id, 'tab' => 'podaci'])
            ->with('status', 'Stranka je ažurirana.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'kind' => ['required', Rule::enum(PartyKind::class)],
            'name' => ['required', 'string', 'max:255'],
            'oib' => ['nullable', 'string', new ValidOib, Rule::unique('parties', 'oib')->where('organization_id', $this->office()->id)->ignore($ignoreId)],
            'mbs' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:34'],
            'contact_person' => ['nullable', 'string', 'max:255'],
        ]);

        $data['oib'] = $data['oib'] !== '' ? $data['oib'] : null;
        $existingConsent = $ignoreId !== null ? Party::query()->find($ignoreId)?->sms_consent_at : null;
        $data['sms_consent_at'] = $request->boolean('sms_consent') ? ($existingConsent ?? now()) : null;

        return $data;
    }

    /**
     * @param  Collection<int, Party>  $parties
     * @param  array{q: string, kind: string, city: string}  $filters
     * @return Collection<int, Party>
     */
    private function filterParties(Collection $parties, array $filters): Collection
    {
        return $parties->filter(function (Party $party) use ($filters): bool {
            if ($filters['kind'] !== '' && $party->kind->value !== $filters['kind']) {
                return false;
            }
            if ($filters['city'] !== '' && $party->city !== $filters['city']) {
                return false;
            }
            if ($filters['q'] === '') {
                return true;
            }

            $haystack = mb_strtolower(implode(' ', array_filter([
                $party->name,
                $party->oib,
                $party->mbs,
                $party->city,
                $party->email,
                $party->phone,
                $party->kind->label(),
            ])));

            return str_contains($haystack, mb_strtolower($filters['q']));
        })->values();
    }

    /**
     * @param  Collection<int, Party>  $parties
     * @return Collection<int, Party>
     */
    private function sortParties(Collection $parties, string $sort, string $dir): Collection
    {
        $collator = class_exists(\Collator::class) ? new \Collator('hr') : null;

        return $parties->sort(function (Party $left, Party $right) use ($sort, $dir, $collator): int {
            $a = match ($sort) {
                'kind' => $left->kind->label(),
                'oib' => (string) ($left->oib ?? ''),
                'mbs' => (string) ($left->mbs ?? ''),
                'city' => (string) ($left->city ?? ''),
                default => (string) $left->name,
            };
            $b = match ($sort) {
                'kind' => $right->kind->label(),
                'oib' => (string) ($right->oib ?? ''),
                'mbs' => (string) ($right->mbs ?? ''),
                'city' => (string) ($right->city ?? ''),
                default => (string) $right->name,
            };

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
}
