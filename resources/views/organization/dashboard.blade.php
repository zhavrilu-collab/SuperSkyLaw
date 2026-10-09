@extends('layouts.app')

@section('title', $organization->name)
@section('nav-suffix', 'Početna')

@section('content')
<div class="pocetna-hero mb-4">
    <h1 class="h4 text-tema mb-1">{{ $organization->name }}</h1>
    <p class="text-muted mb-0">{{ $membership->role->label() }} · plan {{ $organization->plan }}</p>
</div>

<div class="pocetna-mreza mb-4">
    @perm('matters.view')
    <a class="kartica-modula" href="{{ route('organization.matters.index', $organization->slug) }}"><h3>Predmeti</h3><p>Spisi, stranke i kronologija</p></a>
    @endperm
    @perm('parties.view')
    <a class="kartica-modula" href="{{ route('organization.parties.index', $organization->slug) }}"><h3>Stranke</h3><p>Fizičke i pravne osobe</p></a>
    @endperm
    @perm('calendar.view')
    <a class="kartica-modula" href="{{ route('organization.calendar.index', $organization->slug) }}"><h3>Kalendar</h3><p>Ročišta i prekluzivni rokovi</p></a>
    @endperm
    @perm('time.view')
    <a class="kartica-modula" href="{{ route('organization.time.index', $organization->slug) }}"><h3>Vrijeme</h3><p>Štoperica i ručni unos</p></a>
    @endperm
    @if($canFinance)
    <a class="kartica-modula" href="{{ route('organization.invoices.index', $organization->slug) }}"><h3>Financije</h3><p>Računi i troškovi</p></a>
    <a class="kartica-modula" href="{{ route('organization.reports.index', $organization->slug) }}"><h3>Izvještaj</h3><p>Utilizacija, realizacija i naplata</p></a>
    @endif
    @perm('documents.view')
    <a class="kartica-modula" href="{{ route('organization.documents.index', $organization->slug) }}"><h3>Dokumenti</h3><p>Mape predmeta</p></a>
    @endperm
    @if($canManageTeam)
    <a class="kartica-modula" href="{{ route('organization.team.index', $organization->slug) }}"><h3>Ured</h3><p>Tim i podaci ureda</p></a>
    @endif
</div>

@if($critical->isNotEmpty())
<div class="kartica-kontejner mb-3">
    <h2 class="h6 text-tema">Kritični predmeti</h2>
    @foreach($critical as $matter)
        <div class="d-flex justify-content-between border-bottom py-2">
            <a href="{{ route('organization.matters.show', [$organization->slug, $matter->id]) }}">{{ $matter->internal_number }} — {{ $matter->title }}</a>
            @include('partials.matter-phase', ['matter' => $matter])
        </div>
    @endforeach
</div>
@endif
<div class="row g-3">
    <div class="col-lg-4">
        <div class="kartica-kontejner h-100">
            <h2 class="h6 text-tema">Ovaj tjedan</h2>
            @forelse($upcoming as $event)
                <div class="border-bottom py-2">
                    <div class="fw-semibold">{{ $event->title }}</div>
                    <div class="text-muted">{{ $event->starts_at->timezone(config('app.timezone'))->format('d.m. H:i') }} · {{ $event->type->label() }}</div>
                </div>
            @empty
                <p class="text-muted mb-0">Nema nadolazećih termina do kraja tjedna.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="kartica-kontejner h-100">
            <h2 class="h6 text-tema">Zastali rokovi</h2>
            @forelse($overdue as $event)
                <div class="border-bottom py-2">
                    <div class="fw-semibold">{{ $event->title }}</div>
                    <div class="text-muted">{{ $event->starts_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</div>
                </div>
            @empty
                <p class="text-muted mb-0">Nema propuštenih termina.</p>
            @endforelse
        </div>
    </div>
    @if($canFinance)
    <div class="col-lg-4">
        <div class="kartica-kontejner h-100">
            <h2 class="h6 text-tema">Naplata ovaj mjesec</h2>
            <p class="mb-1">Fakturirano: <strong>{{ number_format($invoiced / 100, 2, ',', '.') }} EUR</strong></p>
            <p class="mb-1">Naplaćeno: <strong>{{ number_format($collected / 100, 2, ',', '.') }} EUR</strong></p>
            <p class="mb-0">Nenaplaćeno: <strong>{{ number_format($outstanding / 100, 2, ',', '.') }} EUR</strong></p>
        </div>
    </div>
    @endif
</div>
@endsection
