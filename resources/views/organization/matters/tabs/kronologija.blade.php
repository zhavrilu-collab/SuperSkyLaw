<div class="kartica-kontejner">
    <h2 class="h6 text-tema">Kronologija</h2>
    @forelse($matter->timelineEntries->sortByDesc('occurred_at') as $entry)
        <div class="border-top py-3">
            <div class="fw-semibold">{{ $entry->type->label() }} · {{ $entry->occurred_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }} @if($entry->visible_to_client)<span class="text-tema">· vidi klijent</span>@endif</div>
            <div>{{ $entry->body }}</div>
        </div>
    @empty
        <p class="text-muted">Nema zapisa.</p>
    @endforelse
    @perm('matters.manage')
    <form method="POST" action="{{ route('organization.matters.timeline.store', [$org->slug, $matter->id]) }}" class="border-top pt-3 mt-2">
        @csrf
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label" for="vrstaZapisa">Vrsta</label>
                <select name="type" id="vrstaZapisa" class="form-select">@foreach(\App\Enums\TimelineEntryType::cases() as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="kadZapis">Kad</label>
                <input type="datetime-local" name="occurred_at" id="kadZapis" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="tijeloZapisa">Sadržaj</label>
                <textarea name="body" id="tijeloZapisa" class="form-control" required></textarea>
            </div>
            <div class="col-12"><label class="form-check-label"><input class="form-check-input" type="checkbox" name="visible_to_client" value="1"> Vidljivo klijentu na portalu</label></div>
            <div class="col-12"><button class="btn btn-primary btn-sm" type="submit">Dodaj zapis</button></div>
        </div>
    </form>
    @endperm
</div>
