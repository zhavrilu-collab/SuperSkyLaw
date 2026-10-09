@php
    $dokumenti = $vrsta === ''
        ? $matter->documents
        : $matter->documents->filter(fn ($document) => $document->kind?->value === $vrsta);
@endphp
<div class="kartica-kontejner">
    <h2 class="h6 text-tema">Dokumenti</h2>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-sm {{ $vrsta === '' ? 'btn-dark' : 'btn-outline-secondary' }}" href="{{ route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'dokumenti']) }}">Sve</a>
        @foreach(\App\Enums\DocumentKind::cases() as $kind)
            <a class="btn btn-sm {{ $vrsta === $kind->value ? 'btn-dark' : 'btn-outline-secondary' }}" href="{{ route('organization.matters.show', [$org->slug, $matter->id, 'tab' => 'dokumenti', 'vrsta' => $kind->value]) }}">{{ $kind->label() }}</a>
        @endforeach
    </div>
    @if($dokumenti->isEmpty())
        <p class="text-muted">Nema dokumenata.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Datoteka</th><th>Vrsta</th><th>Stadij</th><th>Datum</th><th>Tko</th></tr>
                </thead>
                <tbody>
                    @foreach($dokumenti->sortByDesc('id') as $document)
                        <tr>
                            <td><a href="{{ route('organization.documents.download', [$org->slug, $document->id]) }}">{{ $document->original_name }}</a></td>
                            <td><span class="badge text-bg-light border">{{ $document->kind?->label() ?: 'Ostalo' }}</span></td>
                            <td>{{ $document->stage?->name ?: '—' }}</td>
                            <td>{{ $document->created_at?->timezone(config('app.timezone'))->format('d.m.Y.') }}</td>
                            <td>{{ $document->uploader?->name ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @perm('documents.manage')
    <form method="POST" action="{{ route('organization.documents.store', $org->slug) }}" enctype="multipart/form-data" class="border-top pt-3 mt-2">
        @csrf
        <input type="hidden" name="matter_id" value="{{ $matter->id }}">
        <input type="hidden" name="return_to" value="matter">
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label" for="vrstaDokumenta">Vrsta</label>
                <select name="kind" id="vrstaDokumenta" class="form-select">
                    @foreach(\App\Enums\DocumentKind::cases() as $kind)
                        <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="stadijDokumenta">Stadij</label>
                <select name="stage_id" id="stadijDokumenta" class="form-select">
                    <option value="">—</option>
                    @foreach($matter->stages as $stage)
                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="datotekaPredmeta">Datoteka</label>
                <input type="file" name="file" id="datotekaPredmeta" class="form-control" required>
            </div>
        </div>
        <button class="btn btn-primary btn-sm mt-2" type="submit">Spremi dokument</button>
    </form>
    @endperm
</div>
