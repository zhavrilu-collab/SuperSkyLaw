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
        display: block;
        padding: .4rem .55rem;
        border-radius: 8px;
        color: #2b3a2b;
        text-decoration: none;
        font-size: 12px;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }
    .zakoni-naziv:hover { background: #f5f7f5; color: #2b3a2b; }
    .zakoni-naziv.is-active {
        background: rgba(var(--tema-rgb), 0.1);
        color: var(--primarna-zelena);
        font-weight: 700;
    }
</style>
@endpush
@section('content')
<h1 class="h5 text-tema mb-3">Zakoni</h1>
<p class="text-muted">Službeni tekstovi objava u Narodnim novinama. Ovo nisu redakcijski pročišćeni tekstovi.</p>
<form method="GET" class="kartica-kontejner mb-3">
    <label class="form-label" for="q">Naziv, broj ili tekst</label>
    <div class="row g-2">
        <div class="col-md-9"><input id="q" name="q" value="{{ $term }}" class="form-control form-control-sm" placeholder="npr. obveznim odnosima ili NN 34/2023"></div>
        <div class="col-md-3"><button class="btn btn-primary btn-sm" type="submit">Traži</button></div>
    </div>
</form>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="kartica-kontejner">
            <div class="zakoni-popis">
                @forelse($works as $work)
                    <a href="{{ route('organization.statutes.index', array_filter([$org->slug, 'q' => $term, 'zakon' => $work->id, 'page' => $works->currentPage() > 1 ? $works->currentPage() : null, 'per_page' => request()->has('per_page') ? $works->perPage() : null])) }}"
                       class="zakoni-naziv {{ $selected && $selected->id === $work->id ? 'is-active' : '' }}">{{ $work->title }}</a>
                @empty
                    <p class="text-muted mb-0">{{ $term === '' ? 'Zakoni se pune iz Narodnih novina.' : 'Nema zakona za taj upit.' }}</p>
                @endforelse
            </div>
            <x-pagination-bar :paginator="$works" />
        </div>
    </div>
    <div class="col-lg-7">
        <div class="kartica-kontejner">
            @if($selected)
                <h2 class="h6 text-tema mb-3" style="overflow-wrap:anywhere">{{ $selected->title }}</h2>
                <div class="table-responsive table-responsive-no-sticky">
                    <table class="table mb-0">
                        <thead><tr><th>Objava</th></tr></thead>
                        <tbody>
                        @forelse($selected->statutes as $statute)
                            <tr>
                                <td><a href="{{ route('organization.statutes.show', [$org->slug, $statute->id]) }}">{{ $statute->citation }}</a></td>
                            </tr>
                        @empty
                            <tr><td class="text-muted">Nema objava za taj upit.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">{{ $term === '' ? 'Zakoni se pune iz Narodnih novina.' : 'Nema zakona za taj upit.' }}</p>
            @endif
        </div>
    </div>
</div>
@endsection
