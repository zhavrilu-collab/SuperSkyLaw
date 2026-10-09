<div class="kartica-kontejner">
    <h2 class="h6 text-tema">Aktivnosti</h2>
    <p class="text-muted">Sve što je na predmetu nastalo: zapisi, rokovi, dokumenti, bilješke, stadiji, sati i novac. Pravni zapis se i dalje upisuje u Kronologiji.</p>
    @forelse($activities as $activity)
        <div class="border-top py-3">
            <div class="fw-semibold">
                {{ $activity->kind }}
                · {{ $activity->at->timezone(config('app.timezone'))->format($activity->at->timezone(config('app.timezone'))->format('H:i:s') === '00:00:00' ? 'd.m.Y.' : 'd.m.Y. H:i') }}
                @if($activity->who)
                    · {{ $activity->who }}
                @endif
            </div>
            <div>{{ $activity->text }}</div>
        </div>
    @empty
        <p class="text-muted mb-0">Nema aktivnosti.</p>
    @endforelse
</div>
