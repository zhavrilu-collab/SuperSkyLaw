@php
    $klijenti = $matter->parties->filter(fn ($link) => $link->side === \App\Enums\PartySide::Client);
    $protiv = $matter->parties->filter(fn ($link) => $link->side === \App\Enums\PartySide::Opposing);
    $aktivni = $matter->stages->first(fn ($stage) => $stage->ended_on === null);
    $vodi = $matter->assignees->first();
    $opisStranke = function ($link, bool $sVrstom): ?string {
        $party = $link->party;
        if ($party === null || blank($party->name)) {
            return null;
        }
        $dijelovi = [$party->name, filled($party->oib) ? 'OIB '.$party->oib : 'OIB —'];
        if ($sVrstom && $party->kind) {
            $dijelovi[] = $party->kind->label();
        }

        return implode(' · ', $dijelovi);
    };
    $urediPodatke = collect(['title', 'kind', 'office_position', 'court_id', 'court_name', 'court_case_number', 'dispute_category_id', 'dispute_value', 'billing_method', 'hourly_rate', 'flat_fee', 'success_fee_note', 'filed_on', 'assignee_ids'])->contains(fn ($key) => $errors->has($key));
    $selectedCourt = old('court_id', $matter->court_id ? (string) $matter->court_id : ($matter->court_name ? 'other' : ''));
    $selectedKind = old('kind', $matter->kind?->value ?? 'civil');
    $selectedCategory = old('dispute_category_id', $matter->dispute_category_id);
    $courtGroups = isset($courts) ? $courts->groupBy(fn ($court) => $court->type->label()) : collect();
    $eur = fn ($cents) => $cents ? number_format($cents / 100, 2, ',', '.').' EUR' : '—';
    $eurUnos = fn ($cents) => $cents ? number_format($cents / 100, 2, '.', '') : '';
    $otvorenaImena = $matter->stages->map(fn ($stage) => mb_strtolower($stage->name));
    $aktivnoIme = mb_strtolower((string) ($aktivni->name ?? ''));
@endphp
<div class="row g-3">
    <div class="col-lg-5">
        <div class="kartica-kontejner h-100 podaci-kartica {{ $urediPodatke ? 'ureduje' : '' }}" id="podaciKartica">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <h2 class="h6 text-tema">Podaci predmeta</h2>
                @perm('matters.manage')
                <button type="button" class="ikona olovka-kartice" id="urediPodatke" aria-label="Uredi podatke">
                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                </button>
                @endperm
            </div>
            <form method="POST" action="{{ route('organization.matters.update', [$org->slug, $matter->id]) }}" class="mt-3">
                @csrf @method('PUT')
                <dl class="predmet-cinjenice">
                    <dt><label for="nazivPredmeta">Naziv</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $matter->title }}</span>
                        <input id="nazivPredmeta" name="title" class="form-control form-control-sm unos @error('title') is-invalid @enderror" value="{{ old('title', $matter->title) }}" required>
                        @error('title')<p class="text-danger small mb-0">{{ $message }}</p>@enderror
                    </dd>
                    <dt><label for="vrstaPredmeta">Vrsta</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $matter->kind->label() }}</span>
                        <select name="kind" id="vrstaPredmeta" class="form-select form-select-sm unos">
                            @foreach(\App\Enums\MatterKind::cases() as $kind)
                                <option value="{{ $kind->value }}" data-description="{{ $kind->description() }}" @selected($selectedKind === $kind->value)>{{ $kind->label() }}</option>
                            @endforeach
                        </select>
                        <div class="form-text unos" id="vrstaOpis"></div>
                    </dd>
                    <dt>Klijent</dt>
                    <dd>{{ $klijenti->map(fn ($link) => $opisStranke($link, true))->filter()->implode(', ') ?: '—' }}</dd>
                    <dt>Protustranka</dt>
                    <dd>{{ $protiv->map(fn ($link) => $opisStranke($link, false))->filter()->implode(', ') ?: '—' }}</dd>
                    <dt><label for="sudPredmeta">Sud</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $matter->courtLabel() ?: '—' }}</span>
                        <select name="court_id" id="sudPredmeta" class="form-select form-select-sm unos">
                            <option value="">—</option>
                            @foreach($courtGroups as $label => $group)
                                <optgroup label="{{ $label }}">
                                    @foreach($group as $court)
                                        <option value="{{ $court->id }}" @selected((string) $selectedCourt === (string) $court->id)>{{ $court->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                            <option value="other" @selected($selectedCourt === 'other')>Nije na popisu</option>
                        </select>
                        <div id="drugiSud" @class(['mt-2', 'vidljiv' => $selectedCourt === 'other'])>
                            <label class="form-label" for="nazivSuda">Naziv suda</label>
                            <input name="court_name" id="nazivSuda" class="form-control form-control-sm" value="{{ old('court_name', $matter->court_id ? '' : $matter->court_name) }}">
                        </div>
                        @error('court_id')<p class="text-danger small mb-0">{{ $message }}</p>@enderror
                    </dd>
                    <dt><label for="brojPredmeta">Sudski broj</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $matter->court_case_number ?: '—' }}</span>
                        <input name="court_case_number" id="brojPredmeta" class="form-control form-control-sm unos" value="{{ old('court_case_number', $matter->court_case_number) }}" placeholder="P-123/2026">
                        @error('court_case_number')<p class="text-danger small mb-0">{{ $message }}</p>@enderror
                    </dd>
                    <dt><label for="podnesenoPredmeta">Podneseno</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $matter->filed_on?->format('d.m.Y.') ?: '—' }}</span>
                        <input type="date" name="filed_on" id="podnesenoPredmeta" class="form-control form-control-sm unos" value="{{ old('filed_on', $matter->filed_on?->format('Y-m-d')) }}">
                    </dd>
                    <dt><label for="predmetSpora">Predmet spora</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $matter->disputeCategory?->name ?: '—' }}</span>
                        <select name="dispute_category_id" id="predmetSpora" class="form-select form-select-sm unos">
                            <option value="">—</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" data-kind="{{ $category->kind->value }}" data-hint="{{ $category->hint }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text unos" id="predmetSporaHint"></div>
                    </dd>
                    <dt><label for="vrijednostPredmeta">Vrijednost</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $eur($matter->dispute_value_cents) }}</span>
                        <input name="dispute_value" id="vrijednostPredmeta" class="form-control form-control-sm unos" value="{{ old('dispute_value', $eurUnos($matter->dispute_value_cents)) }}">
                    </dd>
                    <dt><label for="pozicijaUreda">Pozicija ureda</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $matter->office_position?->label() ?: '—' }}</span>
                        <select name="office_position" id="pozicijaUreda" class="form-select form-select-sm unos" required>
                            <option value="">Odaberite</option>
                            @foreach(\App\Enums\OfficePosition::cases() as $position)
                                <option value="{{ $position->value }}" @selected(old('office_position', $matter->office_position?->value) === $position->value)>{{ $position->label() }}</option>
                            @endforeach
                        </select>
                    </dd>
                    <dt><label for="naplataPredmeta">Naplata</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $matter->billing_method?->label() ?: '—' }}</span>
                        <select name="billing_method" id="naplataPredmeta" class="form-select form-select-sm unos">
                            @foreach(\App\Enums\BillingMethod::cases() as $method)
                                <option value="{{ $method->value }}" @selected(old('billing_method', $matter->billing_method?->value) === $method->value)>{{ $method->label() }}</option>
                            @endforeach
                        </select>
                    </dd>
                    <dt><label for="satnicaPredmeta">Satnica</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $eur($matter->hourly_rate_cents) }}</span>
                        <input name="hourly_rate" id="satnicaPredmeta" class="form-control form-control-sm unos" value="{{ old('hourly_rate', $eurUnos($matter->hourly_rate_cents)) }}">
                    </dd>
                    <dt><label for="pausalPredmeta">Paušal</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $eur($matter->flat_fee_cents) }}</span>
                        <input name="flat_fee" id="pausalPredmeta" class="form-control form-control-sm unos" value="{{ old('flat_fee', $eurUnos($matter->flat_fee_cents)) }}">
                    </dd>
                    <dt><label for="napomenaUspjeha">Napomena o uspjehu</label></dt>
                    <dd class="moze">
                        <span class="prikaz">{{ $matter->success_fee_note ?: '—' }}</span>
                        <textarea name="success_fee_note" id="napomenaUspjeha" class="form-control form-control-sm unos" rows="2">{{ old('success_fee_note', $matter->success_fee_note) }}</textarea>
                    </dd>
                    <dt>Vodi</dt>
                    <dd>{{ $vodi?->name ?: '—' }}</dd>
                    <dt><label for="dodijeljeniPredmeta">Na predmetu</label></dt>
                    <dd class="moze">
                        <span class="prikaz">
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
                        </span>
                        <select name="assignee_ids[]" id="dodijeljeniPredmeta" class="form-select form-select-sm unos" multiple>
                            @foreach($lawyers as $lawyer)
                                <option value="{{ $lawyer->user_id }}" @selected(collect(old('assignee_ids', $matter->assignees->pluck('id')->all()))->contains($lawyer->user_id))>{{ $lawyer->user->name }} ({{ $lawyer->role->label() }})</option>
                            @endforeach
                        </select>
                    </dd>
                    <dt>Otvorio</dt>
                    <dd>{{ $matter->creator?->name ?: '—' }}@if($matter->created_at) · {{ $matter->created_at->timezone(config('app.timezone'))->format('d.m.Y.') }}@endif</dd>
                </dl>
                @perm('matters.manage')
                <div class="akcije-uredivanja">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="odustaniPodaci">Odustani</button>
                    <button class="btn btn-primary btn-sm" type="submit">Spremi</button>
                </div>
                @endperm
            </form>
            @if($matter->conflictChecks->isNotEmpty())
                <p class="small text-muted mt-3 mb-0">Zadnja provjera sukoba: <strong>{{ $matter->conflictChecks->last()->result->label() }}</strong>
                    @if($matter->conflictChecks->last()->checker) ({{ $matter->conflictChecks->last()->checker->name }}) @endif
                </p>
            @endif
            @perm('matters.manage')
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
            <h2 class="h6 text-tema mb-2">Stadij rada</h2>
            @if(!empty($stageTemplate))
                <div class="predlozak">
                    @foreach($stageTemplate as $indeks => $naziv)
                        @php
                            $kljuc = mb_strtolower($naziv);
                            $klasa = $kljuc === $aktivnoIme && $aktivnoIme !== '' ? 'aktivan' : ($otvorenaImena->contains($kljuc) ? 'otvoren' : 'ceka');
                        @endphp
                        @if($klasa === 'ceka')
                            @perm('matters.manage')
                            <button type="button" class="predlozak-korak ceka" data-naziv="{{ $naziv }}">{{ $naziv }}</button>
                            @else
                            <span class="predlozak-korak ceka">{{ $naziv }}</span>
                            @endperm
                        @else
                            <span class="predlozak-korak {{ $klasa }}">{{ $naziv }}</span>
                        @endif
                        @if($indeks < count($stageTemplate) - 1)<span class="predlozak-strelica" aria-hidden="true">&gt;</span>@endif
                    @endforeach
                </div>
            @endif
            @perm('matters.manage')
            <form method="POST" action="{{ route('organization.matters.stages.store', [$org->slug, $matter->id]) }}" class="novi-stadij">
                @csrf
                <div>
                    <label class="form-label" for="nazivStadija">Novi stadij</label>
                    <input name="name" id="nazivStadija" class="form-control form-control-sm" required value="{{ old('_stage') ? $nextStageName : old('name', $nextStageName) }}" placeholder="Naziv stadija">
                </div>
                <div>
                    <label class="form-label" for="zapisStadija">Zapis</label>
                    <input name="body" id="zapisStadija" class="form-control form-control-sm" value="{{ old('_stage') ? '' : old('body') }}" placeholder="Kratki zapis uz stadij">
                </div>
                <button class="btn btn-primary btn-sm" type="submit">Otvori stadij</button>
            </form>
            @if($nextStageName === null)
                <p class="form-text">Predložak je iskorišten. Upišite svoj naziv.</p>
            @endif
            @if(! old('_stage'))
                @error('name')<p class="text-danger small">{{ $message }}</p>@enderror
            @endif
            @endperm
            @if($errors->has('stage'))<p class="text-danger small mb-2">{{ $errors->first('stage') }}</p>@endif
            @if($matter->stages->isNotEmpty())
                @php
                    $stageRows = $matter->stages->values();
                    $lastStage = $stageRows->last();
                    $stageShades = \App\Support\OfficeThemes::stageShades($stageRows->count(), \App\Support\OfficeThemes::palette($org->theme_color, $org->theme_style));
                @endphp
                <div class="traka-stadija mb-3" aria-hidden="true">
                    @foreach($stageRows as $index => $stage)
                        <span style="flex: {{ max(1, (int) round($stageShares[$index] ?? 1)) }} 1 0; background: {{ $stageShades[$index] }}" title="{{ $stage->name }}"></span>
                    @endforeach
                </div>
                @foreach($matter->stages->reverse() as $stage)
                    @php
                        $urediOvaj = (string) old('_stage') === (string) $stage->id;
                        $nazivUnos = $urediOvaj ? old('name', $stage->name) : $stage->name;
                        $zapisUnos = $urediOvaj ? old('body', $stage->body) : $stage->body;
                        $pocetakUnos = $urediOvaj ? old('started_on', $stage->started_on->toDateString()) : $stage->started_on->toDateString();
                        $krajUnos = $urediOvaj ? old('ended_on', $stage->ended_on?->toDateString()) : $stage->ended_on?->toDateString();
                    @endphp
                    <form method="POST" action="{{ route('organization.matters.stages.update', [$org->slug, $matter->id, $stage->id]) }}" class="stadij-red {{ $urediOvaj ? 'ureduje' : '' }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="_stage" value="{{ $stage->id }}">
                        <h3 class="stadij-naslov">
                            <span class="prikaz">{{ $stage->name }}</span>
                            <input name="name" class="form-control form-control-sm unos" required value="{{ $nazivUnos }}" aria-label="Naziv stadija">
                        </h3>
                        <div class="stadij-zapis">
                            <span class="prikaz {{ $stage->body ? '' : 'prazno' }}">{{ $stage->body ?: '—' }}</span>
                            <input name="body" class="form-control form-control-sm unos" value="{{ $zapisUnos }}" placeholder="Zapis" aria-label="Zapis">
                        </div>
                        <div class="stadij-kad">
                            <span class="prikaz">{{ $stage->started_on->format('d.m.Y.') }} — {{ $stage->ended_on ? $stage->ended_on->format('d.m.Y.') : 'danas' }}</span>
                            <span class="unos datumi">
                                <input type="date" name="started_on" class="form-control form-control-sm" required value="{{ $pocetakUnos }}" aria-label="Početak">
                                <span>—</span>
                                <input type="date" name="ended_on" class="form-control form-control-sm" value="{{ $krajUnos }}" aria-label="Kraj">
                            </span>
                        </div>
                        <div class="stadij-akcije">
                            @perm('matters.manage')
                            <button type="button" class="ikona ikona-pregled uredi-stadij" aria-label="Uredi stadij">
                                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                            </button>
                            @if($stage->id === $lastStage->id && $stage->documents->isEmpty())
                            <button type="submit" class="ikona opasno ikona-pregled" form="brisiStadij{{ $stage->id }}" aria-label="Obriši stadij">
                                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3h11V2h-11v1z"/></svg>
                            </button>
                            @endif
                            <button type="button" class="btn btn-outline-secondary btn-sm unos odustani-stadij">Odustani</button>
                            <button class="btn btn-primary btn-sm unos" type="submit">Spremi</button>
                            @endperm
                        </div>
                        @foreach($stage->documents as $document)
                            <a class="badge text-bg-light border stadij-prilog" href="{{ route('organization.documents.download', [$org->slug, $document->id]) }}">{{ $document->original_name }}</a>
                        @endforeach
                        @if($urediOvaj)
                            @error('name')<p class="text-danger small mb-0 stadij-prilog">{{ $message }}</p>@enderror
                            @error('started_on')<p class="text-danger small mb-0 stadij-prilog">{{ $message }}</p>@enderror
                            @error('ended_on')<p class="text-danger small mb-0 stadij-prilog">{{ $message }}</p>@enderror
                            @error('body')<p class="text-danger small mb-0 stadij-prilog">{{ $message }}</p>@enderror
                        @endif
                    </form>
                    @if($stage->id === $lastStage->id && $stage->documents->isEmpty())
                    @perm('matters.manage')
                    <form id="brisiStadij{{ $stage->id }}" method="POST" action="{{ route('organization.matters.stages.destroy', [$org->slug, $matter->id, $stage->id]) }}" class="d-none" onsubmit="return confirm('Obrisati zadnji stadij?')">
                        @csrf @method('DELETE')
                    </form>
                    @endperm
                    @endif
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
<div class="kartica-kontejner mt-3">
    <h2 class="h6 text-tema">Zakoni</h2>
    <p class="small text-muted">Službeni tekst objave u Narodnim novinama, ne redakcijski pročišćeni tekst.</p>
    @forelse($matter->statutes as $statute)
        <div class="d-flex justify-content-between py-1 gap-2">
            <a href="{{ route('organization.statutes.show', [$org->slug, $statute->id]) }}">{{ $statute->citation }} · {{ $statute->title }}</a>
            @perm('matters.manage')
            <form method="POST" action="{{ route('organization.matters.statutes.destroy', [$org->slug, $matter->id, $statute->id]) }}">@csrf @method('DELETE')<button class="btn btn-link btn-sm" type="submit">Ukloni</button></form>
            @endperm
        </div>
    @empty
        <p class="text-muted">Nijedan zakon nije povezan.</p>
    @endforelse
    @perm('matters.manage')
    <form method="GET" action="{{ route('organization.matters.show', [$org->slug, $matter->id]) }}" class="row g-2 mt-2">
        <input type="hidden" name="tab" value="podaci">
        <div class="col-md-8"><label class="form-label" for="traziZakon">Naziv ili NN broj</label><input id="traziZakon" name="zakon" value="{{ request('zakon') }}" class="form-control form-control-sm" placeholder="npr. obveznim ili NN 34/2023"></div>
        <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary btn-sm" type="submit">Traži zakon</button></div>
    </form>
    @foreach($statuteMatches as $statute)
        <form method="POST" action="{{ route('organization.matters.statutes.store', [$org->slug, $matter->id]) }}" class="d-flex justify-content-between align-items-center py-1">
            @csrf
            <input type="hidden" name="statute_id" value="{{ $statute->id }}">
            <span>{{ $statute->citation }} · {{ $statute->title }}</span>
            <button class="btn btn-outline-success btn-sm" type="submit">Poveži</button>
        </form>
    @endforeach
    @endperm
</div>
<script>
(function () {
    const kartica = document.getElementById('podaciKartica');
    const olovka = document.getElementById('urediPodatke');
    const kind = document.getElementById('vrstaPredmeta');
    const category = document.getElementById('predmetSpora');
    const hint = document.getElementById('predmetSporaHint');
    const description = document.getElementById('vrstaOpis');
    const court = document.getElementById('sudPredmeta');
    const other = document.getElementById('drugiSud');

    function syncKind() {
        if (!kind || !category) return;
        const value = kind.value;
        if (description) description.textContent = kind.selectedOptions[0]?.dataset.description || '';
        let selectedStillVisible = false;
        Array.from(category.options).forEach(function (option) {
            if (!option.value) return;
            const visible = option.dataset.kind === value;
            option.hidden = !visible;
            option.disabled = !visible;
            if (visible && option.value === category.value) selectedStillVisible = true;
        });
        if (category.value && !selectedStillVisible) category.value = '';
        if (hint) hint.textContent = category.selectedOptions[0]?.dataset.hint || '';
    }

    function syncCourt() {
        if (!other || !court) return;
        other.classList.toggle('vidljiv', court.value === 'other');
    }

    if (olovka && kartica) {
        olovka.addEventListener('click', function () {
            kartica.classList.add('ureduje');
        });
    }
    const odustani = document.getElementById('odustaniPodaci');
    if (odustani) {
        odustani.addEventListener('click', function () {
            window.location.assign(window.location.pathname + window.location.search);
        });
    }
    if (kind) kind.addEventListener('change', syncKind);
    if (category) category.addEventListener('change', function () {
        if (hint) hint.textContent = category.selectedOptions[0]?.dataset.hint || '';
    });
    if (court) court.addEventListener('change', syncCourt);
    syncKind();
    syncCourt();

    document.querySelectorAll('.predlozak-korak.ceka').forEach(function (gumb) {
        gumb.addEventListener('click', function () {
            const polje = document.getElementById('nazivStadija');
            if (polje && gumb.dataset.naziv) polje.value = gumb.dataset.naziv;
        });
    });

    document.querySelectorAll('.uredi-stadij').forEach(function (gumb) {
        gumb.addEventListener('click', function () {
            const red = gumb.closest('.stadij-red');
            const polja = Array.from(red.querySelectorAll('input')).filter(function (polje) { return polje.name !== '_stage'; });
            red.dataset.snimka = JSON.stringify(polja.map(function (polje) { return polje.value; }));
            document.querySelectorAll('.stadij-red.ureduje').forEach(function (drugi) {
                if (drugi !== red) drugi.classList.remove('ureduje');
            });
            red.classList.add('ureduje');
        });
    });

    document.querySelectorAll('.odustani-stadij').forEach(function (gumb) {
        gumb.addEventListener('click', function () {
            const red = gumb.closest('.stadij-red');
            if (!red.dataset.snimka) {
                window.location.assign(window.location.pathname + window.location.search);
                return;
            }
            const snimka = JSON.parse(red.dataset.snimka);
            Array.from(red.querySelectorAll('input')).filter(function (polje) { return polje.name !== '_stage'; }).forEach(function (polje, indeks) {
                polje.value = snimka[indeks] ?? '';
            });
            red.classList.remove('ureduje');
        });
    });
})();
</script>
