@php
    $klijenti = $matter->parties->filter(fn ($link) => $link->side === \App\Enums\PartySide::Client);
    $protiv = $matter->parties->filter(fn ($link) => $link->side === \App\Enums\PartySide::Opposing);
    $aktivni = $matter->stages->first(fn ($stage) => $stage->ended_on === null);
    $vodi = $matter->assignees->first();
@endphp
<div class="row g-3">
    <div class="col-lg-5">
        <div class="kartica-kontejner h-100">
            <h2 class="h6 text-tema">Podaci predmeta</h2>
            <dl class="predmet-cinjenice">
                <dt>Klijent</dt>
                <dd>{{ $klijenti->map(fn ($link) => $link->party->name)->implode(', ') ?: '—' }}</dd>
                <dt>Protustranka</dt>
                <dd>{{ $protiv->map(fn ($link) => $link->party->name)->implode(', ') ?: '—' }}</dd>
                <dt>Sud</dt>
                <dd>{{ $matter->courtLabel() ?: '—' }}</dd>
                <dt>Sudski broj</dt>
                <dd>{{ $matter->court_case_number ?: '—' }}</dd>
                <dt>Podneseno</dt>
                <dd>{{ $matter->filed_on?->format('d.m.Y.') ?: '—' }}</dd>
                <dt>Predmet spora</dt>
                <dd>{{ $matter->disputeCategory?->name ?: '—' }}</dd>
                <dt>Vrijednost</dt>
                <dd>{{ $matter->dispute_value_cents ? number_format($matter->dispute_value_cents / 100, 2, ',', '.').' EUR' : '—' }}</dd>
                <dt>Pozicija ureda</dt>
                <dd>{{ $matter->office_position?->label() ?: '—' }}</dd>
                <dt>Vodi</dt>
                <dd>{{ $vodi?->name ?: '—' }}</dd>
                <dt>Na predmetu</dt>
                <dd>
                    @if($matter->assignees->isEmpty())
                        —
                    @else
                        <span class="d-inline-flex align-items-center gap-2 flex-wrap">
                            @foreach($matter->assignees as $osoba)
                                <span class="krug-osobe" title="{{ $osoba->name }}">{{ mb_strtoupper(mb_substr($osoba->name, 0, 1).mb_substr(mb_strrchr($osoba->name, ' ') ?: '', 1, 1)) }}</span>
                            @endforeach
                            <span>{{ $matter->assignees->pluck('name')->implode(', ') }}</span>
                        </span>
                    @endif
                </dd>
                <dt>Otvorio</dt>
                <dd>{{ $matter->creator?->name ?: '—' }}@if($matter->created_at) · {{ $matter->created_at->timezone(config('app.timezone'))->format('d.m.Y.') }}@endif</dd>
            </dl>
            @if($matter->conflictChecks->isNotEmpty())
                <p class="small text-muted mt-3 mb-0">Zadnja provjera sukoba: <strong>{{ $matter->conflictChecks->last()->result->label() }}</strong>
                    @if($matter->conflictChecks->last()->checker) ({{ $matter->conflictChecks->last()->checker->name }}) @endif
                </p>
            @endif
            @perm('matters.manage')
            <details class="mt-3">
                <summary class="btn btn-outline-secondary btn-sm">Uredi podatke</summary>
                <form method="POST" action="{{ route('organization.matters.update', [$org->slug, $matter->id]) }}" class="mt-3">
                    @csrf @method('PUT')
                    @include('organization.matters.fields')
                    <button class="btn btn-primary btn-sm" type="submit">Spremi</button>
                </form>
            </details>
            <details class="mt-2">
                <summary class="btn btn-outline-secondary btn-sm">Poveži stranku</summary>
                <form method="POST" action="{{ route('organization.matters.parties.store', [$org->slug, $matter->id]) }}" class="mt-3">
                    @csrf
                    <label class="form-label" for="strankaPredmeta">Stranka</label>
                    <select name="party_id" id="strankaPredmeta" class="form-select mb-2" required>
                        @foreach($parties as $party)<option value="{{ $party->id }}">{{ $party->name }}</option>@endforeach
                    </select>
                    <label class="form-label" for="ulogaStranke">Uloga</label>
                    <select name="role" id="ulogaStranke" class="form-select mb-2">@foreach(\App\Enums\MatterPartyRole::cases() as $role)<option value="{{ $role->value }}">{{ $role->label() }}</option>@endforeach</select>
                    <label class="form-label" for="stranaStranke">Strana</label>
                    <select name="side" id="stranaStranke" class="form-select mb-2">@foreach(\App\Enums\PartySide::cases() as $side)<option value="{{ $side->value }}">{{ $side->label() }}</option>@endforeach</select>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="acknowledge_conflict" value="1" id="ack2"><label class="form-check-label" for="ack2">Potvrđujem provjeru sukoba</label></div>
                    <button class="btn btn-primary btn-sm" type="submit">Poveži stranku</button>
                </form>
            </details>
            @endperm
        </div>
    </div>
    <div class="col-lg-7">
        <div class="kartica-kontejner h-100">
            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                <div>
                    <h2 class="h6 text-tema mb-1">Stadij rada</h2>
                    <p class="mb-0">Aktivni stadij: <strong>{{ $aktivni ? mb_strtoupper($aktivni->name) : '—' }}</strong></p>
                </div>
                @perm('matters.manage')
                <details @if($errors->has('name')) open @endif>
                    <summary class="btn btn-outline-secondary btn-sm">Dodaj stadij</summary>
                    <form method="POST" action="{{ route('organization.matters.stages.store', [$org->slug, $matter->id]) }}" class="mt-2" style="min-width:240px">
                        @csrf
                        <label class="form-label" for="nazivStadija">Naziv</label>
                        <input name="name" id="nazivStadija" class="form-control mb-2" required value="{{ old('name', $nextStageName) }}">
                        <label class="form-label" for="zapisStadija">Zapis</label>
                        <textarea name="body" id="zapisStadija" class="form-control mb-2" rows="3">{{ old('body') }}</textarea>
                        <button class="btn btn-primary btn-sm" type="submit">Otvori stadij</button>
                    </form>
                </details>
                @endperm
            </div>
            @if($matter->stages->isNotEmpty())
                <div class="traka-stadija mb-3" aria-hidden="true">
                    @foreach($matter->stages as $index => $stage)
                        <span class="stadij-{{ $stage->color }}" style="width: {{ $stageShares[$index] ?? 0 }}%" title="{{ $stage->name }}"></span>
                    @endforeach
                </div>
                @foreach($matter->stages->reverse() as $stage)
                    <div class="border-top py-3">
                        <div class="d-flex justify-content-between gap-2">
                            <h3 class="stadij-naslov">{{ $stage->name }}</h3>
                            <span class="small text-muted fw-bold">
                                {{ $stage->started_on->format('d.m.Y.') }}
                                —
                                {{ $stage->ended_on ? $stage->ended_on->format('d.m.Y.') : 'danas' }}
                            </span>
                        </div>
                        @if($stage->body)<p class="mb-2 mt-2">{{ $stage->body }}</p>@endif
                        @foreach($stage->documents as $document)
                            <a class="badge text-bg-light border me-1" href="{{ route('organization.documents.download', [$org->slug, $document->id]) }}">{{ $document->original_name }}</a>
                        @endforeach
                        @perm('matters.manage')
                        <details class="mt-2">
                            <summary class="small">Uredi zapis</summary>
                            <form method="POST" action="{{ route('organization.matters.stages.update', [$org->slug, $matter->id, $stage->id]) }}" class="mt-2">
                                @csrf @method('PUT')
                                <label class="form-label" for="tijeloStadija{{ $stage->id }}">Zapis</label>
                                <textarea name="body" id="tijeloStadija{{ $stage->id }}" class="form-control mb-2" rows="3">{{ $stage->body }}</textarea>
                                <button class="btn btn-primary btn-sm" type="submit">Spremi zapis</button>
                            </form>
                        </details>
                        @endperm
                    </div>
                @endforeach
            @else
                <p class="text-muted mb-0">Stadij još nije otvoren.</p>
            @endif
        </div>
    </div>
</div>
<div class="kartica-kontejner mt-3">
    <h2 class="h6 text-tema">Ured</h2>
    <div class="row g-3">
        <div class="col-lg-6">
            <h3 class="h6">SPNFT</h3>
            <p class="small text-muted">Checklist za predmete u kojima ured ima obvezu sprječavanja pranja novca. Nije vanjski dohvat osobnih podataka.</p>
            @perm('matters.manage')
            <form method="POST" action="{{ route('organization.matters.spnft.required', [$org->slug, $matter->id]) }}" class="mb-2">
                @csrf
                <input type="hidden" name="spnft_required" value="0">
                <label class="form-check-label"><input class="form-check-input" type="checkbox" name="spnft_required" value="1" @checked($matter->spnft_required) onchange="this.form.submit()"> Obveza vrijedi za ovaj predmet</label>
            </form>
            @else
            <p class="mb-2">{{ $matter->spnft_required ? 'Obveza je označena.' : 'Nije označeno.' }}</p>
            @endperm
            @if($matter->spnft_required)
                @foreach(config('spnft.items') as $key => $label)
                    @php $done = $matter->spnftChecks->firstWhere('item', $key); @endphp
                    <form method="POST" action="{{ route('organization.matters.spnft.toggle', [$org->slug, $matter->id]) }}" class="mb-1">
                        @csrf
                        <input type="hidden" name="item" value="{{ $key }}">
                        <button class="btn btn-sm {{ $done ? 'btn-success' : 'btn-outline-secondary' }}" type="submit" @disabled(!auth()->user() || !app(\App\Services\OrganizationRbacService::class)->can($org->id, auth()->id(), 'matters.manage'))>{{ $done ? 'Potvrđeno' : 'Potvrdi' }}</button>
                        <span class="ms-1">{{ $label }}</span>
                    </form>
                @endforeach
            @endif
        </div>
        @perm('walls.manage')
        <div class="col-lg-6">
            <h3 class="h6">Etički zid</h3>
            <p class="small text-muted">Osoba iza zida ne vidi predmet, iako je u istom uredu.</p>
            @forelse($matter->ethicalWalls as $wall)
                <div class="d-flex justify-content-between py-1">
                    <span>{{ $wall->user->name }} · {{ $wall->reason }}</span>
                    <form method="POST" action="{{ route('organization.matters.walls.destroy', [$org->slug, $matter->id, $wall->id]) }}">@csrf @method('DELETE')<button class="btn btn-link btn-sm" type="submit">Ukloni</button></form>
                </div>
            @empty
                <p class="mb-2">Nitko nije isključen.</p>
            @endforelse
            <form method="POST" action="{{ route('organization.matters.walls.store', [$org->slug, $matter->id]) }}" class="mt-2">
                @csrf
                <label class="form-label" for="zidOsoba">Osoba</label>
                <select name="user_id" id="zidOsoba" class="form-select mb-2">
                    @foreach($members as $member)
                        @if($member->user_id !== auth()->id())
                            <option value="{{ $member->user_id }}">{{ $member->user->name }}</option>
                        @endif
                    @endforeach
                </select>
                <label class="form-label" for="zidRazlog">Razlog</label>
                <input name="reason" id="zidRazlog" class="form-control mb-2" required>
                <button class="btn btn-outline-danger btn-sm" type="submit">Zatvori pristup</button>
            </form>
        </div>
        @endperm
    </div>
</div>
