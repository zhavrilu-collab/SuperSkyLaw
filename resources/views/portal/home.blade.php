@extends('layouts.portal')
@section('title', 'Moji predmeti')
@section('content')
<h1 class="h5 text-tema mb-3">Moji predmeti</h1>
<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Spis</th><th>Naziv</th><th>Sud</th></tr></thead>
            <tbody>
            @forelse($matters as $matter)
                <tr>
                    <td><a href="{{ route('portal.matters.show', [$org->slug, $matter->id]) }}">{{ $matter->internal_number }}</a></td>
                    <td>{{ $matter->title }}</td>
                    <td>{{ $matter->courtReference() }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-muted">Nema predmeta.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
