@extends('layouts.app')
@section('title', 'Stranke')
@section('nav-suffix', 'Stranke')
@section('content')
@php
    $columns = [
        'name' => 'Naziv',
        'kind' => 'Vrsta',
        'oib' => 'OIB',
        'mbs' => 'MBS',
        'city' => 'Grad',
    ];
    $filtersActive = $filters['q'] !== '' || $filters['kind'] !== '' || $filters['city'] !== '';
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 text-tema mb-0">Stranke</h1>
    @perm('parties.manage')
    <a class="btn btn-primary btn-sm" href="{{ route('organization.parties.create', $org->slug) }}">Nova stranka</a>
    @endperm
</div>
<div class="kartica-kontejner">
    <form method="GET" class="predmeti-filtri" id="strankeFiltri">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
        <input class="form-control form-control-sm" name="q" value="{{ $filters['q'] }}" placeholder="Pretraži naziv, OIB, MBS ili grad" aria-label="Pretraga stranaka">
        <select class="form-select form-select-sm" name="kind" aria-label="Vrsta">
            <option value="">Sve vrste</option>
            @foreach(\App\Enums\PartyKind::cases() as $kind)
                <option value="{{ $kind->value }}" @selected($filters['kind'] === $kind->value)>{{ $kind->label() }}</option>
            @endforeach
        </select>
        <select class="form-select form-select-sm" name="city" aria-label="Grad">
            <option value="">Svi gradovi</option>
            @foreach($cities as $city)
                <option value="{{ $city }}" @selected($filters['city'] === $city)>{{ $city }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Traži</button>
        @if($filtersActive)
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('organization.parties.index', $org->slug) }}">Poništi</a>
        @endif
    </form>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    @foreach($columns as $key => $label)
                        @php
                            $nextDir = $sort === $key && $dir === 'asc' ? 'desc' : 'asc';
                            $href = route('organization.parties.index', $org->slug).'?'.http_build_query(array_filter(array_merge($filters, ['sort' => $key, 'dir' => $nextDir]), fn ($value) => $value !== ''));
                        @endphp
                        <th aria-sort="{{ $sort === $key ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a class="predmeti-sort" href="{{ $href }}">
                                {{ $label }}
                                <span class="predmeti-arrows" aria-hidden="true">
                                    <span @class(['on' => $sort === $key && $dir === 'asc'])>▲</span>
                                    <span @class(['on' => $sort === $key && $dir === 'desc'])>▼</span>
                                </span>
                            </a>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @forelse($parties as $party)
                <tr>
                    <td><a href="{{ route('organization.parties.show', [$org->slug, $party->id]) }}">{{ $party->name }}</a></td>
                    <td>{{ $party->kind->label() }}</td>
                    <td>{{ $party->oib ?: '—' }}</td>
                    <td>{{ $party->mbs ?: '—' }}</td>
                    <td>{{ $party->city ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">{{ $filtersActive ? 'Nema stranaka koje odgovaraju filtru.' : 'Nema stranaka.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<style>
    .predmeti-filtri { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 16px; }
    .predmeti-filtri .form-control { flex: 1 1 220px; min-width: 180px; }
    .predmeti-filtri .form-select { flex: 1 1 160px; min-width: 140px; max-width: 240px; }
    .predmeti-sort { display: inline-flex; align-items: center; gap: 6px; color: inherit; text-decoration: none; white-space: nowrap; }
    .predmeti-sort:hover { color: inherit; }
    .predmeti-arrows { display: inline-flex; flex-direction: column; font-size: 8px; line-height: .9; color: rgba(0, 0, 0, .28); }
    .predmeti-arrows .on { color: var(--primarna-zelena); }
</style>
<script>
    document.querySelectorAll('#strankeFiltri select').forEach((select) => {
        select.addEventListener('change', () => select.form.requestSubmit());
    });
</script>
@endsection
