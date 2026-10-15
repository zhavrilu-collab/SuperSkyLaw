@php
    $rbac = app(\App\Services\OrganizationRbacService::class);
    $mozeTermin = $rbac->can($org->id, (int) auth()->id(), 'calendar.manage');
    $mozeZapis = $rbac->can($org->id, (int) auth()->id(), 'matters.manage');
    $vrste = [];
    if ($mozeTermin) {
        $vrste['termin'] = 'Termin';
        $vrste['zadatak'] = 'Zadatak';
    }
    if ($mozeZapis) {
        $vrste['zapis'] = 'Zapis';
        $vrste['biljeska'] = 'Bilješka';
    }
    $zadana = old('kind', array_key_first($vrste) ?: 'termin');
    $unosGreske = $errors->hasAny(['kind', 'title', 'type', 'matter_id', 'stage_id', 'court_name', 'starts_at', 'receipt_on', 'term_days', 'responsible_user_id', 'user_id', 'is_preclusive', 'e_oglasna_url', 'notes', 'body', 'due_at', 'occurred_at', 'noted_on', 'visible_to_client']);
@endphp
@if($vrste !== [])
<div class="modal fade" id="unosStavkeProzor" tabindex="-1" aria-labelledby="unosStavkeNaslov" aria-hidden="true" data-vrsta="{{ $zadana }}" data-otvori="{{ $unosGreske ? '1' : '0' }}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('organization.entries.store', $org->slug) }}" id="unosStavkeForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title h6 text-tema" id="unosStavkeNaslov">Novi unos</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" title="Zatvori" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                @if($unosGreske)
                    <div class="alert alert-danger py-2 small">
                        @foreach($errors->all() as $poruka)
                            <div>{{ $poruka }}</div>
                        @endforeach
                    </div>
                @endif
                <div class="mb-2">
                    <label class="form-label" for="unosVrsta">Što</label>
                    <select name="kind" id="unosVrsta" class="form-select form-select-sm">
                        @foreach($vrste as $value => $label)
                            <option value="{{ $value }}" @selected($zadana === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2" data-unos="termin zadatak">
                    <label class="form-label" for="unosNaziv">Naziv</label>
                    <input name="title" id="unosNaziv" class="form-control form-control-sm" value="{{ old('title') }}" required>
                </div>
                <div class="mb-2" data-unos="termin">
                    <label class="form-label" for="unosTipTermina">Vrsta</label>
                    <select name="type" id="unosTipTermina" class="form-select form-select-sm">
                        @foreach(\App\Enums\CourtEventType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('type', 'hearing') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2" data-unos="zapis">
                    <label class="form-label" for="unosTipZapisa">Vrsta zapisa</label>
                    <select name="type" id="unosTipZapisa" class="form-select form-select-sm">
                        @foreach(\App\Enums\TimelineEntryType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="unosPredmet">Predmet</label>
                    <select name="matter_id" id="unosPredmet" class="form-select form-select-sm">
                        <option value="">Bez predmeta</option>
                        @foreach($unosMatters as $matter)
                            <option value="{{ $matter->id }}" @selected((string) old('matter_id') === (string) $matter->id)>{{ $matter->internal_number }} — {{ $matter->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="unosStadij">Stadij</label>
                    <select name="stage_id" id="unosStadij" class="form-select form-select-sm" data-odabrano="{{ old('stage_id') }}">
                        <option value="">Bez stadija</option>
                    </select>
                </div>
                <div class="mb-2" data-unos="termin">
                    <label class="form-label" for="unosSud">Sud</label>
                    <input name="court_name" id="unosSud" class="form-control form-control-sm" value="{{ old('court_name') }}">
                </div>
                <div class="mb-2" data-unos="termin">
                    <label class="form-label" for="unosOglasna">Poveznica na e-Oglasnu</label>
                    <input name="e_oglasna_url" id="unosOglasna" type="url" class="form-control form-control-sm" value="{{ old('e_oglasna_url') }}" placeholder="https://">
                    <div class="form-text">Aplikacija poveznicu ne dohvaća. Ured je upisuje ručno.</div>
                </div>
                <div class="mb-2" data-unos="termin">
                    <label class="form-label" for="unosPocetak">Datum i vrijeme</label>
                    <input type="datetime-local" name="starts_at" id="unosPocetak" class="form-control form-control-sm" value="{{ old('starts_at') }}">
                </div>
                <div data-unos="termin">
                    <p class="small text-muted mb-1">Sudski rok, ako sud odredi broj dana od zaprimanja. Taj izračun zamjenjuje datum i vrijeme iznad.</p>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label" for="unosZaprimanje">Datum zaprimanja</label>
                            <input type="date" name="receipt_on" id="unosZaprimanje" class="form-control form-control-sm" value="{{ old('receipt_on') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="unosDani">Broj dana</label>
                            <input type="number" name="term_days" id="unosDani" min="1" class="form-control form-control-sm" value="{{ old('term_days') }}">
                        </div>
                    </div>
                </div>
                <div class="mb-2" data-unos="zadatak">
                    <label class="form-label" for="unosRok">Rok</label>
                    <input type="datetime-local" name="due_at" id="unosRok" class="form-control form-control-sm" value="{{ old('due_at') }}">
                </div>
                <div class="mb-2" data-unos="zapis">
                    <label class="form-label" for="unosKad">Kad</label>
                    <input type="datetime-local" name="occurred_at" id="unosKad" class="form-control form-control-sm" value="{{ old('occurred_at') }}">
                </div>
                <div class="mb-2" data-unos="biljeska">
                    <label class="form-label" for="unosDatumBiljeske">Datum</label>
                    <input type="date" name="noted_on" id="unosDatumBiljeske" class="form-control form-control-sm" value="{{ old('noted_on') }}">
                </div>
                <div class="mb-2" data-unos="termin">
                    <label class="form-label" for="unosOdgovorni">Odgovorna osoba</label>
                    <select name="responsible_user_id" id="unosOdgovorni" class="form-select form-select-sm">
                        @foreach($unosMembers as $member)
                            <option value="{{ $member->user_id }}" @selected((string) old('responsible_user_id', auth()->id()) === (string) $member->user_id)>{{ $member->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2" data-unos="zadatak">
                    <label class="form-label" for="unosIzvrsitelj">Izvršitelj</label>
                    <select name="user_id" id="unosIzvrsitelj" class="form-select form-select-sm">
                        @foreach($unosMembers as $member)
                            <option value="{{ $member->user_id }}" @selected((string) old('user_id', auth()->id()) === (string) $member->user_id)>{{ $member->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2" data-unos="zadatak zapis biljeska">
                    <label class="form-label" for="unosTijelo">Sadržaj</label>
                    <textarea name="body" id="unosTijelo" class="form-control form-control-sm" rows="3">{{ old('body') }}</textarea>
                </div>
                <div class="mb-2" data-unos="termin">
                    <label class="form-label" for="unosBiljeskaTermina">Bilješka</label>
                    <textarea name="notes" id="unosBiljeskaTermina" class="form-control form-control-sm" rows="2">{{ old('notes') }}</textarea>
                </div>
                <div class="form-check" data-unos="termin">
                    <input class="form-check-input" type="checkbox" name="is_preclusive" value="1" id="unosPrekluzivno" @checked(old('is_preclusive'))>
                    <label class="form-check-label" for="unosPrekluzivno">Prekluzivni rok</label>
                </div>
                <div class="form-check" data-unos="zapis">
                    <input class="form-check-input" type="checkbox" name="visible_to_client" value="1" id="unosKlijent" @checked(old('visible_to_client'))>
                    <label class="form-check-label" for="unosKlijent">Vidljivo klijentu na portalu</label>
                </div>
            </div>
            <div class="modal-footer">
                <x-ikona znak="x" label="Odustani" type="button" data-bs-dismiss="modal" />
                <x-ikona znak="check" label="Spremi" />
            </div>
        </form>
    </div>
</div>
<script type="application/json" id="unosStadiji">@json($unosStages->map(fn ($stage) => ['id' => $stage->id, 'matter_id' => $stage->matter_id, 'name' => $stage->name])->values())</script>
@push('scripts')
<script src="{{ asset('js/unos-stavke.js') }}?v={{ filemtime(public_path('js/unos-stavke.js')) }}"></script>
@endpush
@endif
