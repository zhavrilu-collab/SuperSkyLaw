<div class="kartica-kontejner">
    @if($documents->isEmpty())
        <p class="text-muted mb-0">Nema dokumenata na predmetima ove stranke.</p>
    @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Datoteka</th><th>Predmet</th><th>Mapa</th><th>Datum</th></tr></thead>
                <tbody>
                @foreach($documents as $document)
                    <tr>
                        <td>@perm('documents.view')<a href="{{ route('organization.documents.download', [$org->slug, $document->id]) }}">{{ $document->original_name }}</a>@else{{ $document->original_name }}@endperm</td>
                        <td>@if($document->matter)<a href="{{ route('organization.matters.show', [$org->slug, $document->matter->id]) }}">{{ $document->matter->internal_number }}</a>@else — @endif</td>
                        <td>{{ $document->folder ?: '—' }}</td>
                        <td>{{ $document->created_at?->timezone(config('app.timezone'))->format('d.m.Y.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
