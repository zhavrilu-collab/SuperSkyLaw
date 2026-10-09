<div class="kartica-kontejner">
    <h2 class="h6 text-tema">Aktivnosti</h2>
    <p class="text-muted">Sve što je na predmetu nastalo: zapisi, rokovi, dokumenti, bilješke, stadiji, sati i novac.</p>
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
    @perm('matters.manage')
        <div class="row g-3 border-top pt-3 mt-2">
            <div class="col-lg-5">
                <form method="POST" action="{{ route('organization.matters.notes.store', [$org->slug, $matter->id]) }}">
                    @csrf
                    <label class="form-label" for="novaBiljeska">Nova bilješka</label>
                    <p class="small text-muted">Vide ih samo ljudi u uredu. Ne izlaze klijentu na portal.</p>
                    <textarea name="body" id="novaBiljeska" class="form-control" rows="3" required>{{ old('body') }}</textarea>
                    <button class="btn btn-primary btn-sm mt-2" type="submit">Spremi bilješku</button>
                </form>
            </div>
            <div class="col-lg-7">
                <form method="POST" action="{{ route('organization.matters.timeline.store', [$org->slug, $matter->id]) }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label" for="vrstaZapisa">Novi zapis</label>
                            <select name="type" id="vrstaZapisa" class="form-select">@foreach(\App\Enums\TimelineEntryType::cases() as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="kadZapis">Kad</label>
                            <input type="datetime-local" name="occurred_at" id="kadZapis" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="tijeloZapisa">Sadržaj</label>
                            <textarea name="body" id="tijeloZapisa" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-12"><label class="form-check-label"><input class="form-check-input" type="checkbox" name="visible_to_client" value="1"> Vidljivo klijentu na portalu</label></div>
                        <div class="col-12"><button class="btn btn-primary btn-sm" type="submit">Dodaj zapis</button></div>
                    </div>
                </form>
            </div>
        </div>
    @endperm
</div>
