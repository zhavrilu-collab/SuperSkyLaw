<?php

namespace App\Http\Controllers;

use App\Enums\PartyKind;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\Party;
use App\Rules\ValidOib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartyController extends Controller
{
    use ResolvesOffice;

    public function index(Request $request, string $slug): View
    {
        $this->authorizePerm('parties.view');

        $parties = Party::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)->orWhere('oib', 'like', $term);
                });
            })
            ->orderBy('name')
            ->get();

        return view('organization.parties.index', [
            'parties' => $parties,
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
            ->route('organization.parties.index', $this->office()->slug)
            ->with('status', 'Stranka '.$party->name.' je spremljena.');
    }

    public function edit(string $slug, int $party): View
    {
        $this->authorizePerm('parties.manage');

        return view('organization.parties.form', [
            'party' => Party::query()->findOrFail($party),
        ]);
    }

    public function update(Request $request, string $slug, int $party): RedirectResponse
    {
        $this->authorizePerm('parties.manage');
        $model = Party::query()->findOrFail($party);
        $model->update($this->validated($request, $model->id));

        return redirect()
            ->route('organization.parties.index', $this->office()->slug)
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
}
