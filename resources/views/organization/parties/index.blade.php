@extends('layouts.app')
@section('title', 'Stranke')
@section('nav-suffix', 'Stranke')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h5 text-tema mb-0">Stranke</h1>
    @perm('parties.manage')
    <a class="btn btn-primary btn-sm" href="{{ route('organization.parties.create', $org->slug) }}">Nova stranka</a>
    @endperm
</div>
<form class="mb-3" method="GET">
    @if($kind)<input type="hidden" name="kind" value="{{ $kind }}">@endif
    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Ime ili OIB">
</form>
<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Naziv</th><th>Vrsta</th><th>OIB</th><th>MBS</th><th>Grad</th></tr></thead>
            <tbody>
            @forelse($parties as $party)
                <tr>
                    <td>@perm('parties.manage')<a href="{{ route('organization.parties.edit', [$org->slug, $party->id]) }}">{{ $party->name }}</a>@else{{ $party->name }}@endperm</td>
                    <td>{{ $party->kind->label() }}</td>
                    <td>{{ $party->oib ?: '—' }}</td>
                    <td>{{ $party->mbs ?: '—' }}</td>
                    <td>{{ $party->city ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">Nema stranaka.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
