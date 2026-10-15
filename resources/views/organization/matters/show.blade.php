@extends('layouts.app')
@section('title', $matter->internal_number)
@section('nav-suffix', 'Predmeti')
@push('styles')
<style>
    .predmet-tabovi { border-bottom: 1px solid rgba(var(--tema-rgb), .22); gap: 4px; flex-wrap: wrap; }
    .predmet-tabovi .nav-link { color: #5c6540; font-weight: 700; border: 0; border-bottom: 2px solid transparent; border-radius: 0; margin-bottom: -1px; }
    .predmet-tabovi .nav-link.active { color: var(--primarna-tamna); background: transparent; border-bottom-color: var(--primarna-zelena); }
    .predmet-cinjenice { display: grid; grid-template-columns: 148px 1fr; gap: 6px 12px; margin: 0; }
    .predmet-cinjenice dt { font-size: 12px; font-weight: 700; color: #5c6540; margin: 0; }
    .predmet-cinjenice dd { margin: 0; }
    .traka-stadija { display: flex; height: 16px; gap: 3px; }
    .traka-stadija span { display: block; height: 100%; min-width: 12px; border-radius: 3px; }
    .stadij-naslov { font-size: 13px; letter-spacing: .04em; text-transform: uppercase; margin: 0; }
    .stadij-naslov input { text-transform: none; letter-spacing: 0; font-weight: 400; }
    .krug-osobe { width: 26px; height: 26px; border-radius: 50%; display: inline-grid; place-items: center; font-size: 10px; font-weight: 700; background: var(--svijetlo-zelena); border: 1px solid rgba(var(--tema-rgb), .35); color: var(--primarna-tamna); }
    .predmet-cinjenice dd { min-width: 0; }
    .predmet-cinjenice dt label { font-size: inherit; font-weight: inherit; color: inherit; margin: 0; }
    .stranka-prikaz { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
    .stranka-akcije { display: none; gap: 2px; }
    .podaci-kartica.ureduje .stranka-akcije { display: flex; }
    .stranka-unos { display: none; }
    .stranka-red.ureduje .stranka-prikaz { display: none; }
    .stranka-red.ureduje .stranka-unos { display: block; }
    .stadij-blok { border-top: 1px solid #e6eadc; }
    .stadij-blok .stadij-red { border-top: 0; }
    .stadij-dodatak { padding: 0 0 12px; }
    .podaci-kartica #drugiSud { display: none; }
    .podaci-kartica.ureduje #drugiSud.vidljiv { display: block; }
    .podaci-kartica .unos,
    .stadij-red .unos { display: none; }
    .podaci-kartica .akcije-uredivanja { display: none; }
    .podaci-kartica.ureduje .moze .prikaz, .stadij-red.ureduje .prikaz { display: none; }
    .podaci-kartica.ureduje .moze .unos,
    .stadij-red.ureduje .unos { display: block; }
    .stadij-red.ureduje .stadij-akcije .ikona.unos { display: inline-grid; }
    .podaci-kartica.ureduje .akcije-uredivanja { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }
    .podaci-kartica.ureduje .olovka-kartice, .stadij-red.ureduje .ikona-pregled { display: none; }
    .stadij-red.ureduje .unos.datumi { display: flex; gap: 6px; align-items: center; }
    .stadij-red.ureduje .unos.datumi input { width: 132px; }
    .predlozak { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin: 0 0 12px; }
    .predlozak-dodaj { margin-left: auto; }
    .predlozak-korak { font-size: 12px; letter-spacing: .04em; text-transform: uppercase; font-weight: 700; background: none; border: 0; padding: 0; color: #5c6540; }
    .predlozak-korak.aktivan { color: var(--primarna-tamna); }
    .predlozak-korak.ceka { font-weight: 400; color: #8b9374; }
    button.predlozak-korak.ceka { cursor: pointer; }
    .predlozak-strelica { color: #8b9374; font-size: 12px; }
    .novi-stadij { display: grid; grid-template-columns: minmax(140px, 200px) minmax(0, 1fr) auto; gap: 8px; align-items: end; margin-bottom: 12px; }
    .novi-stadij-napomena { grid-column: 1 / -1; margin: 0; }
    .stadij-red { display: grid; grid-template-columns: 9.5rem minmax(0, 1fr) auto auto; gap: 8px 12px; align-items: center; padding: 11px 0; border-top: 1px solid #e6eadc; }
    .stadij-zapis { min-width: 0; }
    .stadij-zapis .prazno { color: #8b9374; }
    .stadij-kad { font-size: 12px; color: #5c6540; font-weight: 700; white-space: nowrap; }
    .stadij-akcije { display: flex; gap: 2px; justify-content: flex-end; align-items: center; }
    .stadij-prilog { grid-column: 1 / -1; }
    @media (max-width: 900px) {
        .novi-stadij, .stadij-red { grid-template-columns: 1fr; }
        .stadij-kad, .stadij-akcije { justify-self: start; }
    }
    @media (max-width: 576px) {
        .predmet-cinjenice { grid-template-columns: 110px 1fr; }
    }
</style>
@endpush
@section('content')
<div class="d-flex justify-content-between mb-3 gap-3 flex-wrap">
    <div>
        <h1 class="h5 text-tema mb-0">{{ $matter->internal_number }} — {{ $matter->title }}</h1>
        <div class="text-muted d-flex flex-wrap gap-2 align-items-center">
            <span>{{ $matter->kind->label() }}</span>
            @if($matter->office_position)<span>· {{ $matter->office_position->label() }}</span>@endif
            @if($matter->disputeCategory)<span>· {{ $matter->disputeCategory->name }}</span>@endif
            <span>· {{ $matter->courtReference() }}</span>
            @include('partials.matter-phase', ['matter' => $matter])
            @if($matter->outcome)<span>· {{ $matter->outcome->label() }}</span>@endif
        </div>
    </div>
    <div class="d-flex gap-2 predmet-akcije">
        @perm('matters.manage')
        @if($matter->status->value !== 'archived')
        <form method="POST" action="{{ route('organization.matters.pause', [$org->slug, $matter->id]) }}">
            @csrf
            <button class="ikona" type="submit" title="{{ $matter->status->value === 'paused' ? 'Nastavi' : 'Pauziraj' }}" aria-label="{{ $matter->status->value === 'paused' ? 'Nastavi' : 'Pauziraj' }}">
                @if($matter->status->value === 'paused')
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="m11.596 8.697-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393"/></svg>
                @else
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5.5 3.5A1.5 1.5 0 0 1 7 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5m5 0A1.5 1.5 0 0 1 12 5v6a1.5 1.5 0 0 1-3 0V5a1.5 1.5 0 0 1 1.5-1.5"/></svg>
                @endif
            </button>
        </form>
        @endif
        @endperm
        @perm('matters.delete')
        @if($matter->status->value !== 'archived')
        <button class="ikona opasno" type="button" data-bs-toggle="modal" data-bs-target="#arhivaPredmeta" title="Arhiviraj" aria-label="Arhiviraj">
            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M0 2a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1v7.5a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 1 12.5V5a1 1 0 0 1-1-1zm2 3v7.5A1.5 1.5 0 0 0 3.5 14h9a1.5 1.5 0 0 0 1.5-1.5V5zm13-3H1v2h14zM6 8.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5"/></svg>
        </button>
        @else
        <form method="POST" action="{{ route('organization.matters.reopen', [$org->slug, $matter->id]) }}">
            @csrf
            <button class="ikona" type="submit" title="Vrati u rad" aria-label="Vrati u rad">
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/></svg>
            </button>
        </form>
        @endif
        @endperm
    </div>
</div>
@perm('matters.delete')
<div class="modal fade" id="arhivaPredmeta" tabindex="-1" aria-labelledby="arhivaPredmetaNaslov" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('organization.matters.destroy', [$org->slug, $matter->id]) }}">
            @csrf @method('DELETE')
            <div class="modal-header">
                <h2 class="modal-title h6" id="arhivaPredmetaNaslov">Arhiviranje predmeta</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" title="Zatvori" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="ishodArhive">Ishod</label>
                <select name="outcome" id="ishodArhive" class="form-select" required>
                    <option value="">Odaberite ishod</option>
                    @foreach(\App\Enums\MatterOutcome::cases() as $outcome)
                        <option value="{{ $outcome->value }}">{{ $outcome->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button class="ikona" type="button" data-bs-dismiss="modal" title="Odustani" aria-label="Odustani">
                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/></svg>
                </button>
                <button class="ikona opasno" type="submit" title="Arhiviraj" aria-label="Arhiviraj">
                    <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M0 2a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1v7.5a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 1 12.5V5a1 1 0 0 1-1-1zm2 3v7.5A1.5 1.5 0 0 0 3.5 14h9a1.5 1.5 0 0 0 1.5-1.5V5zm13-3H1v2h14zM6 8.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>
@endperm
<ul class="nav predmet-tabovi mb-3">
    @foreach(['podaci' => 'Podaci', 'rokovi' => 'Rokovi', 'dokumenti' => 'Dokumenti', 'aktivnosti' => 'Aktivnosti'] as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('organization.matters.show', [$org->slug, $matter->id, 'tab' => $key]) }}">{{ $label }}</a>
        </li>
    @endforeach
    @if($showLedger)
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'obracun' ? 'active' : '' }}" href="{{ route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'obracun']) }}">Obračun</a>
        </li>
    @endif
</ul>
@include('organization.matters.tabs.'.$tab)
@endsection
