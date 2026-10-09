<div class="kartica-kontejner">
    @if($matters->isEmpty())
        <p class="text-muted mb-0">Stranka nije na nijednom vidljivom predmetu.</p>
    @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Broj</th><th>Naziv</th><th>Uloga</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($matters as $matter)
                    @php $link = $matter->parties->firstWhere('party_id', $party->id); @endphp
                    <tr>
                        <td><a href="{{ route('organization.matters.show', [$org->slug, $matter->id]) }}">{{ $matter->internal_number }}</a></td>
                        <td>{{ $matter->title }}</td>
                        <td>{{ $link?->role?->label() ?: '—' }}</td>
                        <td>@include('partials.matter-phase', ['matter' => $matter])</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
