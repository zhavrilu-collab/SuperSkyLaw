@extends('layouts.app')
@section('title', 'Predmeti')
@section('nav-suffix', 'Predmeti')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 text-tema mb-0">Predmeti</h1>
    @perm('matters.manage')
    <a class="btn btn-primary btn-sm" href="{{ route('organization.matters.create', $org->slug) }}">Novi predmet</a>
    @endperm
</div>
<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Broj</th><th>Naziv</th><th>Vrsta</th><th>Status</th><th>Sudski broj</th></tr></thead>
            <tbody>
            @forelse($matters as $matter)
                <tr>
                    <td><a href="{{ route('organization.matters.show', [$org->slug, $matter->id]) }}">{{ $matter->internal_number }}</a></td>
                    <td>{{ $matter->title }}</td>
                    <td>{{ $matter->kind->label() }}</td>
                    <td>{{ $matter->status->label() }}</td>
                    <td>{{ $matter->courtReference() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">Nema predmeta.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
