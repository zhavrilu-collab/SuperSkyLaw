@extends('layouts.app')
@section('title', 'Dokumenti')
@section('nav-suffix', 'Dokumenti')
@section('content')
<h1 class="h5 text-tema mb-3">Dokumenti</h1>
@perm('documents.manage')
<form method="POST" action="{{ route('organization.documents.store', $org->slug) }}" enctype="multipart/form-data" class="kartica-kontejner mb-3">
    @csrf
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">Predmet</label><select name="matter_id" class="form-select" required>@foreach($matters as $matter)<option value="{{ $matter->id }}">{{ $matter->internal_number }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Mapa</label><input name="folder" class="form-control" value="Podnesci"></div>
        <div class="col-md-3"><label class="form-label">Datoteka</label><input type="file" name="file" class="form-control" required></div>
        <div class="col-md-2"><button class="btn btn-primary" type="submit">Spremi</button></div>
    </div>
</form>
@endperm
<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Predmet</th><th>Mapa</th><th>Datoteka</th><th></th></tr></thead>
            <tbody>
            @forelse($documents as $document)
                <tr>
                    <td>{{ $document->matter->internal_number }}</td>
                    <td>{{ $document->folder }}</td>
                    <td>{{ $document->original_name }}@if($versioning) <span class="text-muted">v{{ $document->version }}</span>@endif</td>
                    <td class="text-end">
                        <a href="{{ route('organization.documents.download', [$org->slug, $document->id]) }}">Preuzmi</a>
                        @planFeature('esign')
                        @perm('documents.manage')
                        <form class="d-inline" method="POST" action="{{ route('organization.documents.sign', [$org->slug, $document->id]) }}">@csrf<button class="btn btn-link btn-sm" type="submit">e-Potpis</button></form>
                        @if($document->signatureRequest)<span class="text-muted">{{ $document->signatureRequest->status->label() }}</span>@endif
                        @endperm
                        @endplanFeature
                        @perm('documents.manage')
                        <form class="d-inline" method="POST" action="{{ route('organization.documents.share', [$org->slug, $document->id]) }}">@csrf<button class="btn btn-link btn-sm" type="submit">{{ $document->shared_with_client ? 'Sakrij od klijenta' : 'Pokaži klijentu' }}</button></form>
                        @endperm
                        @if($versioning)
                            @foreach($document->versions as $version)
                                <a href="{{ route('organization.documents.version.download', [$org->slug, $document->id, $version->version]) }}">v{{ $version->version }}</a>
                            @endforeach
                            @perm('documents.manage')
                            <form class="d-inline" method="POST" action="{{ route('organization.documents.version', [$org->slug, $document->id]) }}" enctype="multipart/form-data">
                                @csrf
                                <input type="file" name="file" class="form-control form-control-sm d-inline-block w-auto" required>
                                <button class="btn btn-sm btn-outline-success" type="submit">Nova verzija</button>
                            </form>
                            @endperm
                        @endif
                        @perm('documents.delete')
                        <form class="d-inline" method="POST" action="{{ route('organization.documents.destroy', [$org->slug, $document->id]) }}">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger" type="submit">Obriši</button></form>
                        @endperm
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">Nema dokumenata.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
