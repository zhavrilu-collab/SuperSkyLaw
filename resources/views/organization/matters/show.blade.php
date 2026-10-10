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
    .krug-osobe { width: 26px; height: 26px; border-radius: 50%; display: inline-grid; place-items: center; font-size: 10px; font-weight: 700; background: var(--svijetlo-zelena); border: 1px solid rgba(var(--tema-rgb), .35); color: var(--primarna-tamna); }
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
