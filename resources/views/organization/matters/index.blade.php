@extends('layouts.app')
@section('title', 'Predmeti')
@section('nav-suffix', 'Predmeti')
@section('content')
@php
    $columns = [
        'number' => 'Broj',
        'party' => 'Stranka',
        'title' => 'Naziv',
        'case_number' => 'Broj predmeta',
        'kind' => 'Vrsta',
        'dispute' => 'Predmet spora',
        'court' => 'Sud',
        'status' => 'Status',
    ];
    $filtersActive = $filters['q'] !== '' || $filters['kind'] !== '' || $filters['dispute'] !== '' || $filters['court'] !== '' || $filters['status'] !== '';
    $courtGroups = $courts->groupBy(fn ($court) => $court->type->label());
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 text-tema mb-0">Predmeti</h1>
    @perm('matters.manage')
    <a class="btn btn-primary btn-sm" href="{{ route('organization.matters.create', $org->slug) }}">Novi predmet</a>
    @endperm
</div>
<div class="kartica-kontejner">
    <form method="GET" class="predmeti-filtri" id="predmetiFiltri">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
        <input class="form-control form-control-sm" name="q" value="{{ $filters['q'] }}" placeholder="Pretraži broj, stranku, naziv ili sud" aria-label="Pretraga predmeta">
        <select class="form-select form-select-sm" name="kind" id="filterKind" aria-label="Vrsta">
            <option value="">Sve vrste</option>
            @foreach(\App\Enums\MatterKind::cases() as $kind)
                <option value="{{ $kind->value }}" @selected($filters['kind'] === $kind->value)>{{ $kind->label() }}</option>
            @endforeach
        </select>
        <select class="form-select form-select-sm" name="dispute" id="filterDispute" aria-label="Predmet spora">
            <option value="">Svi predmeti spora</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" data-kind="{{ $category->kind->value }}" @selected($filters['dispute'] === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select class="form-select form-select-sm" name="court" aria-label="Sud">
            <option value="">Svi sudovi</option>
            @foreach($courtGroups as $label => $group)
                <optgroup label="{{ $label }}">
                    @foreach($group as $court)
                        <option value="{{ $court->id }}" @selected($filters['court'] === (string) $court->id)>{{ $court->name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
            @if($extraCourts->isNotEmpty())
                <optgroup label="Nije na popisu">
                    @foreach($extraCourts as $name)
                        <option value="text:{{ $name }}" @selected($filters['court'] === 'text:'.$name)>{{ $name }}</option>
                    @endforeach
                </optgroup>
            @endif
        </select>
        <select class="form-select form-select-sm" name="status" aria-label="Status">
            <option value="">Svi statusi</option>
            @foreach(\App\Enums\MatterPhase::cases() as $phase)
                <option value="{{ $phase->value }}" @selected($filters['status'] === $phase->value)>{{ $phase->label() }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Traži</button>
        @if($filtersActive)
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('organization.matters.index', $org->slug) }}">Poništi</a>
        @endif
    </form>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    @foreach($columns as $key => $label)
                        @php
                            $nextDir = $sort === $key && $dir === 'asc' ? 'desc' : 'asc';
                            $href = route('organization.matters.index', $org->slug).'?'.http_build_query(array_filter(array_merge($filters, ['sort' => $key, 'dir' => $nextDir]), fn ($value) => $value !== ''));
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
            @forelse($matters as $matter)
                <tr>
                    <td><a href="{{ route('organization.matters.show', [$org->slug, $matter->id]) }}">{{ $matter->internal_number }}</a></td>
                    <td>{{ $matter->clientLabel() !== '' ? $matter->clientLabel() : '—' }}</td>
                    <td>{{ $matter->title }}</td>
                    <td>{{ $matter->court_case_number ?: '—' }}</td>
                    <td>{{ $matter->kind->label() }}</td>
                    <td>{{ $matter->disputeCategory?->name ?: '—' }}</td>
                    <td>{{ $matter->courtLabel() !== '' ? $matter->courtLabel() : '—' }}</td>
                    <td>@include('partials.matter-phase', ['matter' => $matter])</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted">{{ $filtersActive ? 'Nema predmeta koji odgovaraju filtru.' : 'Nema predmeta.' }}</td></tr>
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
    const filterKind = document.getElementById('filterKind');
    const filterDispute = document.getElementById('filterDispute');
    const syncDisputeFilter = () => {
        const value = filterKind.value;
        for (const option of filterDispute.options) {
            if (!option.value) continue;
            const visible = value === '' || option.dataset.kind === value;
            option.hidden = !visible;
            if (!visible && option.selected) filterDispute.value = '';
        }
    };
    syncDisputeFilter();
    filterKind.addEventListener('change', syncDisputeFilter);
    document.querySelectorAll('#predmetiFiltri select').forEach((select) => {
        select.addEventListener('change', () => select.form.requestSubmit());
    });
</script>
@endsection
