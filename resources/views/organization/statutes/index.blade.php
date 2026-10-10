@extends('layouts.app')
@section('title', 'Zakoni')
@section('nav-suffix', 'Zakoni')
@push('styles')
<style>
    .zakoni-popis {
        display: flex;
        flex-direction: column;
        gap: 2px;
        max-height: min(70vh, calc(100vh - 16rem));
        overflow: auto;
    }
    .zakoni-naziv {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: .75rem;
        padding: .4rem .55rem;
        border-radius: 8px;
        color: #2b3a2b;
        text-decoration: none;
        font-size: 12px;
        line-height: 1.35;
    }
    .zakoni-naslov { overflow-wrap: anywhere; }
    .zakoni-podrucje { flex: 0 0 auto; color: #6b7a6b; font-weight: 400; white-space: nowrap; }
    .zakoni-naziv:hover { background: #f5f7f5; color: #2b3a2b; }
    .zakoni-naziv.is-active {
        background: rgba(var(--tema-rgb), 0.1);
        color: var(--primarna-zelena);
        font-weight: 700;
    }
    .zakoni-naziv.is-active .zakoni-podrucje { color: var(--primarna-zelena); }
    .predmeti-sort { display: inline-flex; align-items: center; gap: 6px; color: inherit; text-decoration: none; white-space: nowrap; }
    .predmeti-sort:hover { color: inherit; }
    .predmeti-arrows { display: inline-flex; flex-direction: column; font-size: 8px; line-height: .9; color: rgba(0, 0, 0, .28); }
    .predmeti-arrows .on { color: var(--primarna-zelena); }
    .zakoni-naziv-celija { overflow-wrap: anywhere; }
</style>
@endpush
@section('content')
<h1 class="h5 text-tema mb-3">Zakoni</h1>
<p class="text-muted">Službeni tekstovi objava u Narodnim novinama. Ovo nisu redakcijski pročišćeni tekstovi.</p>
<form method="GET" class="kartica-kontejner mb-3">
    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label" for="q">Naziv, broj ili tekst</label>
            <input id="q" name="q" value="{{ $term }}" class="form-control form-control-sm" placeholder="npr. obveznim odnosima ili NN 34/2023">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="podrucje">Područje</label>
            <select id="podrucje" name="podrucje" class="form-select form-select-sm">
                <option value="">Sva područja</option>
                @foreach(\App\Enums\StatuteArea::cases() as $option)
                    <option value="{{ $option->value }}" @selected($area?->value === $option->value)>{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary btn-sm" type="submit">Traži</button></div>
    </div>
</form>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="kartica-kontejner">
            <div class="zakoni-popis">
                @forelse($works as $work)
                    <a href="{{ route('organization.statutes.index', array_filter([$org->slug, 'q' => $term, 'podrucje' => $area?->value, 'zakon' => $work->id, 'page' => $works->currentPage() > 1 ? $works->currentPage() : null, 'per_page' => request()->has('per_page') ? $works->perPage() : null, 'sort' => request()->has('sort') ? $sort : null, 'dir' => request()->has('sort') ? $dir : null])) }}"
                       class="zakoni-naziv {{ $selected && $selected->id === $work->id ? 'is-active' : '' }}">
                        <span class="zakoni-naslov">{{ $work->title }}</span>
                        @if($work->area)<span class="zakoni-podrucje">{{ $work->area->label() }}</span>@endif
                    </a>
                @empty
                    <p class="text-muted mb-0">{{ $term === '' && $area === null ? 'Zakoni se pune iz Narodnih novina.' : 'Nema zakona za taj upit.' }}</p>
                @endforelse
            </div>
            <x-pagination-bar :paginator="$works" />
        </div>
    </div>
    <div class="col-lg-7">
        <div class="kartica-kontejner">
            @if($selected)
                <h2 class="h6 text-tema {{ $selected->area ? 'mb-1' : 'mb-3' }}" style="overflow-wrap:anywhere">{{ $selected->title }}</h2>
                @if($selected->area)<p class="text-muted mb-3">{{ $selected->area->label() }}</p>@endif
                <div class="table-responsive table-responsive-no-sticky">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                @foreach(['objava' => 'Objava', 'datum' => 'Datum', 'naziv' => 'Naziv'] as $key => $label)
                                    @php
                                        $nextDir = $sort === $key && $dir === 'asc' ? 'desc' : 'asc';
                                        $href = route('organization.statutes.index', array_filter([
                                            $org->slug,
                                            'q' => $term,
                                            'podrucje' => $area?->value,
                                            'zakon' => $selected->id,
                                            'page' => $works->currentPage() > 1 ? $works->currentPage() : null,
                                            'per_page' => request()->has('per_page') ? $works->perPage() : null,
                                            'sort' => $key,
                                            'dir' => $nextDir,
                                        ]));
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
                        @forelse($selected->statutes as $statute)
                            <tr>
                                <td class="text-nowrap"><a href="{{ route('organization.statutes.show', [$org->slug, $statute->id]) }}">{{ $statute->citation }}</a></td>
                                <td class="text-nowrap">{{ $statute->published_on?->format('d.m.Y.') ?: '—' }}</td>
                                <td class="zakoni-naziv-celija">{{ $statute->title }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">Nema objava za taj upit.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">{{ $term === '' && $area === null ? 'Zakoni se pune iz Narodnih novina.' : 'Nema zakona za taj upit.' }}</p>
            @endif
        </div>
    </div>
</div>
@endsection
