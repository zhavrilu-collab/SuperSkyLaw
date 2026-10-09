<div class="kartica-kontejner">
    <h2 class="h6 text-tema">Bilješke</h2>
    <p class="small text-muted">Vide ih samo ljudi u uredu. Ne izlaze klijentu na portal.</p>
    @forelse($matter->notes as $note)
        <div class="border-top py-3">
            <div class="fw-semibold">{{ $note->user?->name ?: 'Ured' }} · {{ $note->created_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</div>
            <div>{{ $note->body }}</div>
        </div>
    @empty
        <p class="text-muted">Nema bilješki.</p>
    @endforelse
    @perm('matters.manage')
    <form method="POST" action="{{ route('organization.matters.notes.store', [$org->slug, $matter->id]) }}" class="border-top pt-3 mt-2">
        @csrf
        <label class="form-label" for="novaBiljeska">Nova bilješka</label>
        <textarea name="body" id="novaBiljeska" class="form-control" rows="3" required>{{ old('body') }}</textarea>
        <button class="btn btn-primary btn-sm mt-2" type="submit">Spremi bilješku</button>
    </form>
    @endperm
</div>
