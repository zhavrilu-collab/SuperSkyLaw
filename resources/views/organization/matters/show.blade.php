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
    .ikona { width: 28px; height: 28px; border: 0; background: transparent; border-radius: 6px; color: #5c6540; display: inline-grid; place-items: center; padding: 0; }
    .ikona:hover { background: #f3f6ea; color: var(--primarna-tamna); }
    .ikona.opasno { color: #9b1c1c; }
    .ikona.opasno:hover { background: #fdecec; }
    .ikona svg { width: 15px; height: 15px; display: block; }
    .ikona:disabled { color: #c5cbb0; cursor: not-allowed; }
    .ikona:disabled:hover { background: transparent; color: #c5cbb0; }
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
    .stadij-red.ureduje .stadij-akcije .btn.unos { display: inline-block; }
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
            <button class="btn btn-outline-secondary btn-sm" type="submit">{{ $matter->status->value === 'paused' ? 'Nastavi' : 'Pauziraj' }}</button>
        </form>
        @endif
        @endperm
        @perm('matters.delete')
        @if($matter->status->value !== 'archived')
        <button class="btn btn-outline-danger btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#arhivaPredmeta">Arhiviraj</button>
        @else
        <form method="POST" action="{{ route('organization.matters.reopen', [$org->slug, $matter->id]) }}">
            @csrf
            <button class="btn btn-outline-secondary btn-sm" type="submit">Vrati u rad</button>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
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
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-dismiss="modal">Odustani</button>
                <button class="btn btn-danger btn-sm" type="submit">Arhiviraj</button>
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
