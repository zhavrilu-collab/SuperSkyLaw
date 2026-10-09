<div class="kartica-kontejner">
    <h2 class="h6 text-tema">Rokovi i ročišta</h2>
    @if($matter->courtEvents->isEmpty())
        <p class="text-muted">Nema upisanih rokova.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>Što</th><th>Kad</th><th>Tko</th><th>Stanje</th></tr>
                </thead>
                <tbody>
                    @foreach($matter->courtEvents->sortBy('starts_at') as $event)
                        <tr>
                            <td>{{ $event->title }}@if($event->court_name)<div class="small text-muted">{{ $event->court_name }}</div>@endif</td>
                            <td>{{ $event->starts_at->timezone(config('app.timezone'))->format($event->type->isDeadline() ? 'd.m.Y.' : 'd.m.Y. H:i') }}</td>
                            <td>{{ $event->responsible?->name ?: '—' }}</td>
                            <td>
                                @if($event->completed_at)
                                    Završeno
                                @elseif($event->type === \App\Enums\CourtEventType::Hearing)
                                    Zakazano
                                @else
                                    Otvoren
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(!empty($deadlineRules))
        <h2 class="h6 text-tema mt-3">Zakonski rok</h2>
        <p class="small text-muted">{{ config('statutory_deadlines.disclaimer') }}</p>
        @if(!empty($deadlinePreview))
            <div class="alert alert-warning">
                {{ $deadlinePreview['label'] }} istječe <strong>{{ \Illuminate\Support\Carbon::parse($deadlinePreview['due_on'])->format('d.m.Y.') }}</strong>.
                {{ $deadlinePreview['basis'] }}
            </div>
        @endif
        @foreach($deadlineRules as $rule)
            <form method="POST" action="{{ route('organization.matters.deadline.store', [$org->slug, $matter->id]) }}" class="border-top py-2">
                @csrf
                <input type="hidden" name="rule" value="{{ $rule['key'] }}">
                <div class="fw-semibold">{{ $rule['label'] }}</div>
                <div class="small text-muted">{{ $rule['trigger'] }} · {{ $rule['amount'] }} {{ ['days' => 'dana', 'months' => 'mjeseci', 'years' => 'godina'][$rule['unit']] }} · {{ $rule['basis'] }}</div>
                <div class="row g-2 mt-1">
                    <div class="col-md-6">
                        <label class="form-label" for="primitak{{ $rule['key'] }}">Datum primitka</label>
                        <input type="date" name="receipt_on" id="primitak{{ $rule['key'] }}" class="form-control" required value="{{ old('rule') === $rule['key'] ? old('receipt_on') : '' }}">
                    </div>
                    <div class="col-md-6 d-flex gap-2 align-items-end">
                        <button class="btn btn-outline-secondary btn-sm" name="intent" value="preview" type="submit">Izračunaj</button>
                        <button class="btn btn-primary btn-sm" name="intent" value="save" type="submit">Upiši rok</button>
                    </div>
                </div>
            </form>
        @endforeach
    @endif

    <h2 class="h6 text-tema mt-4">Zastara</h2>
    <p class="small text-muted">{{ config('limitation.disclaimer') }}</p>
    @if($matter->limitationEstimate)
        <div class="mb-2">Orijentacijski rok: <strong>{{ $matter->limitationEstimate->suggested_on->format('d.m.Y.') }}</strong> · {{ $matter->limitationEstimate->basisLabel() }}</div>
    @endif
    @perm('matters.manage')
    <form method="POST" action="{{ route('organization.matters.limitation.store', [$org->slug, $matter->id]) }}">
        @csrf
        <label class="form-label" for="osnovaZastare">Osnova</label>
        <select name="basis" id="osnovaZastare" class="form-select mb-2">
            @foreach(config('limitation.bases') as $key => $basis)
                <option value="{{ $key }}">{{ $basis['label'] }}</option>
            @endforeach
        </select>
        <label class="form-label" for="pocetakZastare">Početak</label>
        <input type="date" name="starts_on" id="pocetakZastare" class="form-control mb-2" required>
        <button class="btn btn-primary btn-sm" type="submit">Izračunaj i upiši rok</button>
    </form>
    @endperm
</div>
