@extends('layouts.app')
@section('title', 'Zakoni')
@section('nav-suffix', 'Zakoni')
@push('styles')
<style>
    .zakoni-podrucja { display: flex; flex-wrap: wrap; gap: .35rem; }
    .zakoni-podrucja .btn { font-size: 12px; }
    .zakoni-red.is-active td { background: rgba(var(--tema-rgb), 0.1); }
    .zakoni-red a { color: #2b3a2b; text-decoration: none; }
    .zakoni-red.is-active a { color: var(--primarna-zelena); font-weight: 700; }
    .zakoni-naslov { overflow-wrap: anywhere; }
    .zakoni-pogodak { background: #ffe56b; color: inherit; padding: 0; }
    .predmeti-sort { display: inline-flex; align-items: center; gap: 6px; color: inherit; text-decoration: none; white-space: nowrap; }
    .predmeti-sort:hover { color: inherit; }
    .predmeti-arrows { display: inline-flex; flex-direction: column; font-size: 8px; line-height: .9; color: rgba(0, 0, 0, .28); }
    .predmeti-arrows .on { color: var(--primarna-zelena); }
    .zakoni-naziv-celija { overflow-wrap: anywhere; }
    .zakoni-tekst { overflow-x: auto; }
</style>
@endpush
@section('content')
<h1 class="h5 text-tema mb-3">Zakoni</h1>
<p class="text-muted">Službeni tekstovi objava u Narodnim novinama. Ovo nisu redakcijski pročišćeni tekstovi.</p>
@php
    $lawFilters = array_filter([
        'q' => $term,
        'podrucje' => $area?->value,
        'per_page' => request()->has('per_page') ? $works->perPage() : null,
        'lsort' => request()->has('lsort') ? $listSort : null,
        'ldir' => request()->has('lsort') ? $listDir : null,
        'sort' => request()->has('sort') ? $sort : null,
        'dir' => request()->has('sort') ? $dir : null,
    ], fn ($value) => $value !== null && $value !== '');
@endphp
<div class="zakoni-podrucja mb-3">
    <a class="btn btn-sm {{ $area === null ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('organization.statutes.index', array_filter([$org->slug, ...$lawFilters, 'podrucje' => null], fn ($value) => $value !== null && $value !== '')) }}">Sva područja</a>
    @foreach(\App\Enums\StatuteArea::cases() as $option)
        <a class="btn btn-sm {{ $area === $option ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('organization.statutes.index', [$org->slug, ...$lawFilters, 'podrucje' => $option->value]) }}">{{ $option->label() }}</a>
    @endforeach
</div>
<form method="GET" class="kartica-kontejner mb-3" id="zakoni-trazi">
    @if($area)<input type="hidden" name="podrucje" value="{{ $area->value }}">@endif
    @if(request()->has('lsort'))
        <input type="hidden" name="lsort" value="{{ $listSort }}">
        <input type="hidden" name="ldir" value="{{ $listDir }}">
    @endif
    @if(request()->has('sort'))
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
    @endif
    @if(request()->has('per_page'))<input type="hidden" name="per_page" value="{{ $works->perPage() }}">@endif
    <label class="form-label" for="q">Naziv ili broj</label>
    <div class="row g-2">
        <div class="col-md-9"><input id="q" name="q" value="{{ $term }}" class="form-control form-control-sm" placeholder="npr. sav, obveznim odnosima ili NN 34/2023"></div>
        <div class="col-md-3"><button class="btn btn-primary btn-sm" type="submit">Traži</button></div>
    </div>
</form>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="kartica-kontejner">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            @foreach(['naziv' => 'Naziv', 'podrucje' => 'Područje'] as $key => $label)
                                @php
                                    $nextDir = $listSort === $key && $listDir === 'asc' ? 'desc' : 'asc';
                                    $href = route('organization.statutes.index', array_filter([
                                        $org->slug,
                                        ...$lawFilters,
                                        'lsort' => $key,
                                        'ldir' => $nextDir,
                                    ], fn ($value) => $value !== null && $value !== ''));
                                @endphp
                                <th aria-sort="{{ $listSort === $key ? ($listDir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                    <a class="predmeti-sort" href="{{ $href }}">
                                        {{ $label }}
                                        <span class="predmeti-arrows" aria-hidden="true">
                                            <span @class(['on' => $listSort === $key && $listDir === 'asc'])>▲</span>
                                            <span @class(['on' => $listSort === $key && $listDir === 'desc'])>▼</span>
                                        </span>
                                    </a>
                                    </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($works as $work)
                        @php
                            $href = route('organization.statutes.index', array_filter([
                                $org->slug,
                                ...$lawFilters,
                                'zakon' => $work->id,
                                'page' => $works->currentPage() > 1 ? $works->currentPage() : null,
                            ], fn ($value) => $value !== null && $value !== ''));
                        @endphp
                        <tr class="zakoni-red {{ $selected && $selected->id === $work->id ? 'is-active' : '' }}">
                            <td class="zakoni-naslov"><a href="{{ $href }}">{!! \App\Support\TextFold::highlight($work->title, $term) !!}</a></td>
                            <td class="text-nowrap"><a href="{{ $href }}">{{ $work->area?->label() }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted">{{ $term === '' && $area === null ? 'Zakoni se pune iz Narodnih novina.' : 'Nema zakona za taj upit.' }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
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
                                            ...$lawFilters,
                                            'zakon' => $selected->id,
                                            'page' => $works->currentPage() > 1 ? $works->currentPage() : null,
                                            'sort' => $key,
                                            'dir' => $nextDir,
                                        ], fn ($value) => $value !== null && $value !== ''));
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
                @if($reading)
                    <h3 class="h6 text-tema mt-4 mb-1">{{ $readingConsolidated ? 'Pročišćeni tekst' : 'Osnovni tekst' }}</h3>
                    <p class="small text-muted mb-2">
                        <a href="{{ route('organization.statutes.show', [$org->slug, $reading->id]) }}">{{ $reading->citation }}</a>@if($reading->published_on) · {{ $reading->published_on->format('d.m.Y.') }}@endif.
                        @if($readingConsolidated)
                            Službeni pročišćeni tekst objavljen u Narodnim novinama. Izmjene objavljene poslije tog broja nisu unesene u ovaj tekst.
                        @else
                            Službeni tekst osnovne objave. Narodne novine nisu objavile kasniji pročišćeni tekst ovog zakona.
                        @endif
                    </p>
                    @if($reading->text_html)
                        <div class="zakoni-tekst">{!! $reading->text_html !!}</div>
                    @else
                        <p class="text-muted mb-0">Tekst još nije preuzet. Ostaje poveznica na objavu.</p>
                    @endif
                @endif
            @else
                <p class="text-muted mb-0">{{ $term === '' && $area === null ? 'Zakoni se pune iz Narodnih novina.' : 'Nema zakona za taj upit.' }}</p>
            @endif
        </div>
    </div>
</div>
@endsection
