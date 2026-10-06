@extends('layouts.app')
@section('title', 'Vrijeme')
@section('nav-suffix', 'Vrijeme')
@section('content')
<h1 class="h5 text-tema mb-3">Vrijeme</h1>
<div class="row g-3 mb-3">
    <div class="col-md-6"><div class="kartica-kontejner"><div class="text-muted">WIP, odobreno a nefakturirano</div><strong>{{ number_format($wipCents / 100, 2, ',', '.') }} EUR</strong></div></div>
    <div class="col-md-6"><div class="kartica-kontejner"><div class="text-muted">Čeka odobrenje</div><strong>{{ number_format($draftCents / 100, 2, ',', '.') }} EUR</strong></div></div>
</div>
@if($running)
    <div class="alert alert-success">Štoperica radi od {{ $running->started_at->timezone(config('app.timezone'))->format('H:i') }} — {{ $running->description }}
        <form class="d-inline" method="POST" action="{{ route('organization.time.stop', [$org->slug, $running->id]) }}">@csrf<button class="btn btn-sm btn-dark" type="submit">Zaustavi</button></form>
    </div>
@endif
<div class="row g-3">
    <div class="col-lg-5">
        @perm('time.manage')
        <form method="POST" action="{{ route('organization.time.start', $org->slug) }}" class="kartica-kontejner mb-3">
            @csrf
            <h2 class="h6 text-tema">Štoperica</h2>
            <select name="matter_id" class="form-select mb-2" required>@foreach($matters as $matter)<option value="{{ $matter->id }}">{{ $matter->internal_number }} — {{ $matter->title }}</option>@endforeach</select>
            <input name="description" class="form-control mb-2" placeholder="Opis radnje" required>
            <button class="btn btn-primary btn-sm" type="submit">Pokreni</button>
        </form>
        <form method="POST" action="{{ route('organization.time.store', $org->slug) }}" class="kartica-kontejner">
            @csrf
            <h2 class="h6 text-tema">Ručni unos</h2>
            <select name="matter_id" class="form-select mb-2" required>@foreach($matters as $matter)<option value="{{ $matter->id }}">{{ $matter->internal_number }}</option>@endforeach</select>
            <input name="description" class="form-control mb-2" placeholder="Npr. Izrada žalbe" required>
            <input name="minutes" type="number" min="1" class="form-control mb-2" placeholder="Minute" required>
            <button class="btn btn-primary btn-sm" type="submit">Unesi</button>
        </form>
        @endperm
    </div>
    <div class="col-lg-7">
        <div class="kartica-kontejner">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Predmet</th><th>Opis</th><th>Min</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($entries as $entry)
                        <tr>
                            <td>{{ $entry->matter->internal_number }}</td>
                            <td>{{ $entry->description }}</td>
                            <td>{{ $entry->minutes }}</td>
                            <td>{{ $entry->status->label() }}</td>
                            <td>
                                @if($requiresApproval)
                                @perm('time.approve')
                                @if(!$entry->isRunning() && !$entry->invoice_id && in_array($entry->status->value, ['draft', 'approved', 'non_billable']))
                                <div class="d-flex gap-1">
                                    @if($entry->status->value !== 'approved')
                                    <form method="POST" action="{{ route('organization.time.approve', [$org->slug, $entry->id]) }}">@csrf<button class="btn btn-sm btn-outline-success" type="submit">Odobri</button></form>
                                    @endif
                                    @if($entry->status->value !== 'written_off')
                                    <form method="POST" action="{{ route('organization.time.write-off', [$org->slug, $entry->id]) }}">@csrf<button class="btn btn-sm btn-outline-secondary" type="submit">Otpis</button></form>
                                    @endif
                                    @if($entry->status->value !== 'non_billable')
                                    <form method="POST" action="{{ route('organization.time.non-billable', [$org->slug, $entry->id]) }}">@csrf<button class="btn btn-sm btn-outline-secondary" type="submit">Nenaplativo</button></form>
                                    @endif
                                </div>
                                @endif
                                @endperm
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
