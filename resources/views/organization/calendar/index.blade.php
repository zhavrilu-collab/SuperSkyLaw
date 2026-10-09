@extends('layouts.app')
@section('title', 'Kalendar')
@section('nav-suffix', 'Kalendar')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 text-tema mb-0">Kalendar</h1>
</div>

<div id="officeKalendar" data-can-manage="{{ app(\App\Services\OrganizationRbacService::class)->can($org->id, (int) auth()->id(), 'calendar.manage') ? '1' : '0' }}">
    <div class="events-board-toolbar mb-2">
        <div class="events-board-filters">
            <input type="search" id="pretragaTermina" class="form-control form-control-sm events-board-search" placeholder="Pretraži termine...">
            <select id="filterGrupa" class="form-select form-select-sm events-board-filter">
                <option value="all">Ročišta i rokovi</option>
                <option value="hearing">Ročišta</option>
                <option value="deadline">Rokovi</option>
            </select>
        </div>
        <div class="events-view-toolbar">
            <div class="btn-group btn-group-sm events-view-toggle" role="group">
                <button type="button" class="btn btn-outline-secondary" data-events-view="lista" title="Lista">☰ Lista</button>
                <button type="button" class="btn btn-outline-secondary active" data-events-view="kalendar" title="Kalendar">▦ Kalendar</button>
            </div>
            <div class="btn-group btn-group-sm kal-layout-toolbar" role="group" id="kalLayoutToolbarWrap">
                <button type="button" class="btn btn-outline-secondary" data-kal-layout="dan">Dan</button>
                <button type="button" class="btn btn-outline-secondary" data-kal-layout="tjedan">Tjedan</button>
                <button type="button" class="btn btn-outline-secondary active" data-kal-layout="mjesec">Mjesec</button>
            </div>
        </div>
    </div>

    <div id="viewKalendar" class="events-kalendar-view">
        <div class="kalendar-nav-row">
            <div class="kalendar-nav">
                <button type="button" class="btn btn-outline-secondary btn-sm kal-nav-btn" id="btnKalPrethodni" title="Prethodni">‹</button>
                <h2 id="naslovKalendara" class="mb-0 kal-nav-naslov" title="Prikaži termine ovog razdoblja u listi"></h2>
                <button type="button" class="btn btn-outline-secondary btn-sm kal-nav-btn" id="btnKalSljedeci" title="Sljedeći">›</button>
            </div>
        </div>
        <div class="kalendar-kontejner">
            <div class="kalendar-grid kal-layout-mjesec" id="kalendarGrid"></div>
            <div class="mt-3 d-flex gap-3 flex-wrap events-kalendar-legenda" id="kalendarLegenda"></div>
        </div>
    </div>

    <div id="viewLista" class="d-none">
        <div id="listaOpseg" class="small text-muted mb-2"></div>
        <div class="kartica-kontejner">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Termin</th><th>Vrsta</th><th>Predmet</th><th></th></tr></thead>
                    <tbody id="listaTermina">
                    @forelse($events as $event)
                        @php
                            $group = $event->type->isDeadline() ? 'deadline' : (in_array($event->type, [\App\Enums\CourtEventType::Hearing, \App\Enums\CourtEventType::Meeting, \App\Enums\CourtEventType::Inspection], true) ? 'hearing' : 'other');
                            $search = mb_strtolower($event->title.' '.$event->type->label().' '.($event->matter?->internal_number ?? '').' '.($event->court_name ?? ''));
                        @endphp
                        <tr data-event-id="{{ $event->id }}" data-date="{{ $event->starts_at->timezone(config('app.timezone'))->toDateString() }}" data-group="{{ $group }}" data-search="{{ $search }}">
                            <td>{{ $event->starts_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}<div class="text-muted">{{ $event->title }}</div></td>
                            <td>{{ $event->type->label() }} @if($event->is_preclusive)<span class="text-danger">prekluzivno</span>@endif</td>
                            <td>{{ $event->matter?->internal_number ?: '—' }}
                                @if($event->e_oglasna_url)<div><a href="{{ $event->e_oglasna_url }}" target="_blank" rel="noopener">e-Oglasna</a></div>@endif
                            </td>
                            <td>
                                @perm('calendar.manage')
                                @if(!$event->completed_at)
                                <form method="POST" action="{{ route('organization.calendar.complete', [$org->slug, $event->id]) }}">@csrf<button class="btn btn-sm btn-outline-success" type="submit">Obavljeno</button></form>
                                @endif
                                @if($event->matter && $event->matter->parties->isNotEmpty())
                                <form method="POST" action="{{ route('organization.calendar.sms', [$org->slug, $event->id]) }}" class="mt-1">
                                    @csrf
                                    <select name="party_id" class="form-select form-select-sm mb-1">
                                        @foreach($event->matter->parties as $link)
                                            <option value="{{ $link->party_id }}">{{ $link->party->name }}{{ $link->party->sms_consent_at ? '' : ' (nema privole)' }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">SMS</button>
                                </form>
                                @endif
                                @endperm
                            </td>
                        </tr>
                    @empty
                        <tr class="lista-prazno-server"><td colspan="4" class="text-muted">Nema termina.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p id="listaPrazno" class="text-muted d-none mt-2">Nema termina za odabrani prikaz.</p>
    </div>
</div>

@if(session('hearing_offer'))
    @php $offer = $events->firstWhere('id', session('hearing_offer')); @endphp
    @if($offer)
    <form method="POST" action="{{ route('organization.calendar.charge', [$org->slug, $offer->id]) }}" class="kartica-kontejner mt-3">
        @csrf
        <h2 class="h6 text-tema">Nagrada za ročište {{ $offer->starts_at->timezone(config('app.timezone'))->format('d.m.Y.') }}</h2>
        <p class="small text-muted">Bodovi se računaju iz vrijednosti spora prema tarifi HOK-a. Stavka se ne upisuje dok je ne potvrdite.</p>
        <div class="row g-2">
            <div class="col-md-5">
                <select name="tariff_action" class="form-select">
                    <option value="rociste">Ročište o glavnoj stvari</option>
                    <option value="rociste_procesno">Ročište o procesnim pitanjima</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="audience" class="form-select">
                    @foreach(\App\Enums\FeeAudience::cases() as $audience)
                        <option value="{{ $audience->value }}">{{ $audience->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-primary btn-sm" type="submit">Upiši stavku</button></div>
        </div>
    </form>
    @endif
@endif
@perm('calendar.manage')
@php
    $terminGreske = $errors->hasAny(['title', 'type', 'matter_id', 'court_name', 'starts_at', 'receipt_on', 'term_days', 'responsible_user_id', 'is_preclusive', 'e_oglasna_url']);
@endphp
<div class="modal fade" id="noviTerminProzor" tabindex="-1" aria-labelledby="noviTerminNaslov" aria-hidden="true" data-otvori="{{ $terminGreske ? '1' : '0' }}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('organization.calendar.store', $org->slug) }}" id="noviTerminForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title h6 text-tema" id="noviTerminNaslov">Novi termin</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                @if($terminGreske)
                    <div class="alert alert-danger py-2 small">
                        @foreach(['title', 'type', 'matter_id', 'court_name', 'starts_at', 'receipt_on', 'term_days', 'responsible_user_id', 'e_oglasna_url'] as $polje)
                            @error($polje)<div>{{ $message }}</div>@enderror
                        @endforeach
                    </div>
                @endif
                <div class="mb-2">
                    <label class="form-label" for="noviTerminNaziv">Naziv</label>
                    <input name="title" id="noviTerminNaziv" class="form-control form-control-sm" value="{{ old('title') }}" required>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="noviTerminVrsta">Vrsta</label>
                    <select name="type" id="noviTerminVrsta" class="form-select form-select-sm">
                        @foreach(\App\Enums\CourtEventType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(old('type', 'hearing') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="noviTerminPredmet">Predmet</label>
                    <select name="matter_id" id="noviTerminPredmet" class="form-select form-select-sm">
                        <option value="">Bez predmeta</option>
                        @foreach($matters as $matter)
                            <option value="{{ $matter->id }}" @selected((string) old('matter_id') === (string) $matter->id)>{{ $matter->internal_number }} — {{ $matter->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="noviTerminSud">Sud</label>
                    <input name="court_name" id="noviTerminSud" class="form-control form-control-sm" value="{{ old('court_name') }}">
                </div>
                <div class="mb-2">
                    <label class="form-label" for="noviTerminOglasna">Poveznica na e-Oglasnu</label>
                    <input name="e_oglasna_url" id="noviTerminOglasna" type="url" class="form-control form-control-sm" value="{{ old('e_oglasna_url') }}" placeholder="https://">
                    <div class="form-text">Aplikacija poveznicu ne dohvaća. Ured je upisuje ručno.</div>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="noviTerminPocetak">Datum i vrijeme</label>
                    <input type="datetime-local" name="starts_at" id="noviTerminPocetak" class="form-control form-control-sm" value="{{ old('starts_at') }}">
                </div>
                <p class="small text-muted mb-1">Sudski rok, ako sud odredi broj dana od zaprimanja. Taj izračun zamjenjuje datum i vrijeme iznad.</p>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label" for="noviTerminZaprimanje">Datum zaprimanja</label>
                        <input type="date" name="receipt_on" id="noviTerminZaprimanje" class="form-control form-control-sm" value="{{ old('receipt_on') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="noviTerminDani">Broj dana</label>
                        <input type="number" name="term_days" id="noviTerminDani" min="1" class="form-control form-control-sm" value="{{ old('term_days') }}">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="noviTerminOdgovorni">Odgovorna osoba</label>
                    <select name="responsible_user_id" id="noviTerminOdgovorni" class="form-select form-select-sm">
                        @foreach($members as $member)
                            <option value="{{ $member->user_id }}" @selected((string) old('responsible_user_id', auth()->id()) === (string) $member->user_id)>{{ $member->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_preclusive" value="1" id="pre" @checked(old('is_preclusive'))>
                    <label class="form-check-label" for="pre">Prekluzivni rok</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Odustani</button>
                <button class="btn btn-primary btn-sm" type="submit">Spremi</button>
            </div>
        </form>
    </div>
</div>
@endperm
<div class="row g-3 mt-1">
    @planFeature('calendar_sync')
    <div class="col-lg-5">
        <div class="kartica-kontejner">
            <h2 class="h6 text-tema">Vanjski kalendar</h2>
            <p class="mb-2"><a href="{{ route('organization.calendar.export', $org->slug) }}">Preuzmi ICS</a></p>
            <p class="small text-muted">Google ili Outlook mogu se pretplatiti na: {{ route('calendar.feed', $org->feedToken('calendar_feed_token')) }}</p>
            @perm('calendar.manage')
            <form method="POST" action="{{ route('organization.calendar.import', $org->slug) }}" class="row g-2">
                @csrf
                <div class="col-md-8"><input name="feed_url" type="url" class="form-control" placeholder="Adresa vanjskog ICS feeda" required></div>
                <div class="col-md-4"><button class="btn btn-primary btn-sm" type="submit">Uvezi</button></div>
            </form>
            @endperm
        </div>
    </div>
    @endplanFeature
</div>
<script type="application/json" id="kalendarPodaci">@json($calendarEvents)</script>
@endsection

@push('styles')
<style>
    .events-board-toolbar { display: flex; flex-wrap: wrap; gap: 10px 14px; align-items: flex-start; justify-content: space-between; }
    .events-board-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; flex: 1 1 420px; min-width: 0; }
    .events-board-search { flex: 1 1 220px; min-width: 180px; max-width: 320px; }
    .events-board-filter { flex: 0 1 190px; min-width: 150px; max-width: 210px; }
    .events-view-toolbar { flex: 0 0 auto; display: flex; flex-direction: column; align-items: flex-end; gap: 6px; margin-left: auto; }
    .events-view-toggle .btn, .kal-layout-toolbar .btn { margin: 0; white-space: nowrap; }
    .events-kalendar-view { margin-top: 12px; }
    .kalendar-kontejner { background: white; border-radius: 16px; padding: 14px 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); border: 1px solid rgba(var(--tema-rgb), 0.12); }
    .kalendar-nav-row { display: flex; justify-content: center; align-items: center; width: 100%; margin-bottom: 8px; }
    .kalendar-nav { display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
    .kalendar-nav .kal-nav-naslov { font-weight: 700; color: var(--primarna-zelena); font-size: 1.1rem; cursor: pointer; margin: 0; text-align: center; white-space: nowrap; padding: 0 4px; }
    .kalendar-nav .kal-nav-btn { width: 32px; min-width: 32px; padding: 2px 0; font-size: 1.15rem; line-height: 1; }
    .kalendar-grid.kal-layout-mjesec { display: flex; flex-direction: column; gap: 4px; }
    .kal-mjesec-head, .kal-mjesec-dani, .kal-mjesec-lanes, .kal-mjesec-more { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 1px 4px; }
    .kal-dan-naziv { text-align: center; font-size: 11px; font-weight: 700; color: #888; padding: 2px 0; }
    .kal-mjesec-tjedan { display: flex; flex-direction: column; gap: 1px; min-height: 78px; border: 1px solid rgba(var(--tema-rgb), 0.16); border-radius: 8px; padding: 2px 3px; background: #fff; }
    .kal-layout-mjesec .kal-dan { min-height: 22px; background: transparent; border: none; padding: 1px 3px; border-radius: 6px; cursor: pointer; }
    .kal-layout-mjesec .kal-dan:hover { background: rgba(var(--tema-rgb), 0.08); }
    .kal-layout-mjesec .kal-dan.danas { background: rgba(var(--tema-rgb), 0.16); color: var(--primarna-tamna); font-weight: 700; }
    .kal-dan.drugi-mjesec { opacity: 0.4; }
    .kal-dan-zaglavlje { display: flex; justify-content: space-between; align-items: flex-start; gap: 4px; }
    .kal-broj { font-weight: 600; font-size: 12px; line-height: 1.2; }
    .kal-dodaj-btn { width: 18px; height: 18px; padding: 0; border: 1px dashed rgba(var(--tema-rgb), 0.45); border-radius: 4px; background: #fff; color: var(--primarna-zelena); font-size: 13px; font-weight: 700; line-height: 1; cursor: pointer; }
    .kal-event-bar { --kal-bar: var(--primarna-zelena); display: block; width: 100%; margin: 0; border: none; border-radius: 4px; padding: 1px 5px; min-height: 16px; text-align: left; font-size: 10px; font-weight: 600; line-height: 1.25; color: #fff; background: var(--kal-bar); cursor: pointer; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
    .kal-event-bar-single { background: rgba(var(--tema-rgb), 0.1); color: #1a2e1a; box-shadow: inset 3px 0 0 var(--kal-bar); }
    .kal-event-bar.gotovo { opacity: 0.55; }
    .kal-event-bar-cont-left { border-top-left-radius: 0; border-bottom-left-radius: 0; }
    .kal-event-bar-cont-right { border-top-right-radius: 0; border-bottom-right-radius: 0; }
    .kal-event-more { display: block; width: 100%; border: none; background: transparent; color: var(--primarna-tamna); font-size: 10px; font-weight: 700; text-align: left; padding: 0 2px; cursor: pointer; }
    .kalendar-grid.kal-layout-tjedan { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; min-height: 360px; }
    .kalendar-grid.kal-layout-dan { display: block; min-height: 320px; }
    .kal-dan-stupac, .kal-dan-puna { background: #f8f9fa; border: 1px solid rgba(var(--tema-rgb), 0.16); border-radius: 10px; min-height: 320px; display: flex; flex-direction: column; overflow: hidden; }
    .kal-dan-stupac.danas, .kal-dan-puna.danas { border-color: var(--primarna-zelena); }
    .kal-stupac-naslov, .kal-dan-puna-zaglavlje { display: flex; justify-content: space-between; align-items: center; gap: 6px; padding: 8px 10px; background: rgba(var(--tema-rgb), 0.08); border-bottom: 1px solid rgba(var(--tema-rgb), 0.16); font-size: 12px; font-weight: 700; color: var(--primarna-tamna); }
    .kal-stupac-dogadjaji { padding: 6px; display: flex; flex-direction: column; gap: 4px; }
    .kal-dogadjaj { border: none; border-left: 3px solid var(--kal-bar, var(--primarna-zelena)); background: #fff; border-radius: 6px; padding: 5px 7px; font-size: 11px; cursor: pointer; text-align: left; }
    .kal-dogadjaj.gotovo { opacity: 0.55; }
    .kal-dog-vrijeme { color: #666; font-size: 10px; margin-right: 4px; }
    .kal-raspored-prazno { font-size: 11px; color: #aaa; padding: 4px 0 8px; }
    .events-kalendar-legenda { font-size: 11px; }
    .kal-tocka { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    #listaTermina tr.istaknuto td { background: rgba(var(--tema-rgb), 0.12); }
    @@media (max-width: 900px) {
        .kalendar-grid.kal-layout-tjedan { grid-template-columns: 1fr; }
        .kal-mjesec-head { display: none; }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/office-calendar.js') }}?v={{ filemtime(public_path('js/office-calendar.js')) }}"></script>
@endpush
