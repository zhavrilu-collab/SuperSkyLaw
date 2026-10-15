@extends('layouts.app')
@section('title', 'Kalendar')
@section('nav-suffix', 'Kalendar')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 text-tema mb-0">Kalendar</h1>
</div>

<div id="officeKalendar" data-can-manage="{{ app(\App\Services\OrganizationRbacService::class)->can($org->id, (int) auth()->id(), 'calendar.manage') ? '1' : '0' }}" data-kalendar-url="{{ url('/'.$org->slug.'/kalendar') }}" data-zadaci-url="{{ url('/'.$org->slug.'/zadaci') }}">
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
            <div class="events-view-toggle" role="group" aria-label="Prikaz">
                <x-ikona znak="list" label="Lista" type="button" data-events-view="lista" />
                <x-ikona znak="calendar" label="Kalendar" type="button" class="active" data-events-view="kalendar" />
            </div>
            <div class="kal-layout-toolbar" role="group" id="kalLayoutToolbarWrap" aria-label="Razdoblje">
                <x-ikona znak="cal-dan" label="Dan" type="button" data-kal-layout="dan" />
                <x-ikona znak="cal-tjedan" label="Tjedan" type="button" data-kal-layout="tjedan" />
                <x-ikona znak="cal-mjesec" label="Mjesec" type="button" class="active" data-kal-layout="mjesec" />
            </div>
        </div>
    </div>

    <div id="viewKalendar" class="events-kalendar-view">
        <div class="kalendar-split">
            <div class="kalendar-lijevo">
                <div class="kalendar-nav-row">
                    <div class="kalendar-nav">
                        <x-ikona znak="chevron-left" label="Prethodni" type="button" id="btnKalPrethodni" />
                        <h2 id="naslovKalendara" class="mb-0 kal-nav-naslov" title="Prikaži nadolazeće"></h2>
                        <x-ikona znak="chevron-right" label="Sljedeći" type="button" id="btnKalSljedeci" />
                    </div>
                </div>
                <div class="kalendar-kontejner">
                    <div class="kalendar-grid kal-layout-mjesec" id="kalendarGrid"></div>
                    <div class="mt-3 d-flex gap-3 flex-wrap events-kalendar-legenda" id="kalendarLegenda"></div>
                </div>
            </div>
            <aside class="kalendar-desno" id="kalendarPanel" aria-live="polite"></aside>
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
                                <form method="POST" action="{{ route('organization.calendar.complete', [$org->slug, $event->id]) }}">@csrf<x-ikona znak="check" label="Obavljeno" /></form>
                                @endif
                                @if($event->matter && $event->matter->parties->isNotEmpty())
                                <form method="POST" action="{{ route('organization.calendar.sms', [$org->slug, $event->id]) }}" class="mt-1">
                                    @csrf
                                    <select name="party_id" class="form-select form-select-sm mb-1">
                                        @foreach($event->matter->parties as $link)
                                            <option value="{{ $link->party_id }}">{{ $link->party->name }}{{ $link->party->sms_consent_at ? '' : ' (nema privole)' }}</option>
                                        @endforeach
                                    </select>
                                    <x-ikona znak="chat" label="SMS" />
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
            <div class="col-md-3"><x-ikona znak="check" label="Upiši stavku" /></div>
        </div>
    </form>
    @endif
@endif
@include('organization.partials.unos-stavke', [
    'unosMatters' => $matters,
    'unosStages' => $stages,
    'unosMembers' => $members,
])
<script type="application/json" id="kalendarPodaci">@json($calendarBoard)</script>
@endsection

@push('styles')
<style>
    .events-board-toolbar { display: flex; flex-wrap: wrap; gap: 10px 14px; align-items: flex-start; justify-content: space-between; }
    .events-board-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; flex: 1 1 420px; min-width: 0; }
    .events-board-search { flex: 1 1 220px; min-width: 180px; max-width: 320px; }
    .events-board-filter { flex: 0 1 190px; min-width: 150px; max-width: 210px; }
    .events-view-toolbar { flex: 0 0 auto; display: flex; flex-direction: row; flex-wrap: nowrap; align-items: center; gap: 8px; margin-left: auto; }
    .events-view-toggle, .kal-layout-toolbar { display: flex; flex-direction: row; gap: 2px; align-items: center; }
    .events-view-toolbar .ikona { width: 28px; height: 28px; }
    .events-view-toolbar .ikona.active {
        background: var(--primarna-zelena);
        color: var(--tekst-na-primarnoj);
        border-color: transparent;
        box-shadow: none;
    }
    .events-view-toolbar .ikona.active:hover {
        background: var(--primarna-tamna);
        color: #fff;
    }
    body.ikone-negativ .events-view-toolbar .ikona.active {
        background: var(--primarna-tamna);
        box-shadow: inset 0 0 0 1px var(--tekst-na-primarnoj);
    }
    .events-kalendar-view { margin-top: 12px; }
    .kalendar-split { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(260px, 0.9fr); gap: 16px; align-items: start; }
    .kalendar-desno { background: white; border-radius: 16px; padding: 14px 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); border: 1px solid rgba(var(--tema-rgb), 0.12); max-height: calc(100vh - 160px); overflow: auto; position: sticky; top: 12px; }
    .kal-panel-naslov { font-size: 14px; font-weight: 700; color: var(--primarna-tamna); margin: 0 0 8px; }
    .kal-panel-veza { border: 0; background: transparent; color: var(--primarna-zelena); font-size: 12px; font-weight: 700; padding: 0; cursor: pointer; }
    .kal-panel-dan { font-size: 11px; font-weight: 700; color: #5c6540; margin: 10px 0 4px; }
    .kal-panel-stavka { display: block; width: 100%; text-align: left; border: 0; border-left: 3px solid var(--kal-bar, var(--primarna-zelena)); background: #f7f8f4; border-radius: 6px; padding: 6px 8px; margin: 0 0 4px; cursor: pointer; }
    .kal-panel-stavka .vrijeme { font-size: 11px; color: #666; margin-right: 4px; }
    .kal-panel-stavka .naziv { font-size: 12px; font-weight: 700; }
    .kal-panel-stavka .meta { display: block; font-size: 11px; color: #5c6540; }
    .kal-panel-stavka.gotovo { opacity: 0.55; }
    .kal-detalj { display: grid; grid-template-columns: 92px 1fr; gap: 4px 8px; margin: 0; font-size: 12px; }
    .kal-detalj dt { margin: 0; font-weight: 700; color: #5c6540; }
    .kal-detalj dd { margin: 0; }
    .kal-akcija { border: 0; background: var(--primarna-zelena); color: #fff; border-radius: 6px; font-size: 12px; font-weight: 700; padding: 4px 10px; cursor: pointer; }
    .kal-dan.odabran, .kal-dan-stupac.odabran, .kal-dan-puna.odabran { box-shadow: inset 0 0 0 2px var(--primarna-zelena); }
    .kal-event-bar.istaknuto, .kal-dogadjaj.istaknuto { outline: 2px solid var(--primarna-tamna); }
    .kalendar-kontejner { background: white; border-radius: 16px; padding: 14px 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); border: 1px solid rgba(var(--tema-rgb), 0.12); }
    .kalendar-nav-row { display: flex; justify-content: center; align-items: center; width: 100%; margin-bottom: 8px; }
    .kalendar-nav { display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
    .kalendar-nav .kal-nav-naslov { font-weight: 700; color: var(--primarna-zelena); font-size: 1.1rem; cursor: pointer; margin: 0; text-align: center; white-space: nowrap; padding: 0 4px; }
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
        .kalendar-split { grid-template-columns: 1fr; }
        .kalendar-desno { position: static; max-height: none; }
        .kalendar-grid.kal-layout-tjedan { grid-template-columns: 1fr; }
        .kal-mjesec-head { display: none; }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/office-calendar.js') }}?v={{ filemtime(public_path('js/office-calendar.js')) }}"></script>
@endpush
