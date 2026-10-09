@extends('layouts.app')
@section('title', 'Biblioteka')
@section('nav-suffix', 'Biblioteka')
@section('content')
<h1 class="h5 text-tema mb-3">Biblioteka zakona</h1>
<p class="text-muted">Službeni tekstovi objava u Narodnim novinama. Ovo nisu redakcijski pročišćeni tekstovi.</p>
<form method="GET" class="kartica-kontejner mb-3">
    <label class="form-label" for="q">Naziv, broj ili tekst</label>
    <div class="row g-2">
        <div class="col-md-9"><input id="q" name="q" value="{{ $term }}" class="form-control form-control-sm" placeholder="npr. obveznim odnosima ili NN 34/2023"></div>
        <div class="col-md-3"><button class="btn btn-primary btn-sm" type="submit">Traži</button></div>
    </div>
</form>
<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Objava</th><th>Naziv</th><th>Datum</th></tr></thead>
            <tbody>
            @forelse($statutes as $statute)
                <tr>
                    <td><a href="{{ route('organization.statutes.show', [$org->slug, $statute->id]) }}">{{ $statute->citation }}</a></td>
                    <td>{{ $statute->title }}</td>
                    <td>{{ $statute->published_on?->format('d.m.Y.') ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-muted">{{ $term === '' ? 'Biblioteka se puni iz Narodnih novina.' : 'Nema zakona za taj upit.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $statutes->links() }}</div>
</div>
@endsection
