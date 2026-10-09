@php
    $selectedCourt = old('court_id', $matter->court_id ? (string) $matter->court_id : ($matter->court_name ? 'other' : ''));
    $selectedKind = old('kind', $matter->kind?->value ?? 'civil');
    $selectedCategory = old('dispute_category_id', $matter->dispute_category_id);
    $courtGroups = $courts->groupBy(fn ($court) => $court->type->label());
@endphp
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Naziv</label><input name="title" class="form-control" value="{{ old('title', $matter->title) }}" required></div>
    <div class="col-md-3 mb-3"><label class="form-label">Vrsta</label>
        <select name="kind" id="vrstaPredmeta" class="form-select">
            @foreach(\App\Enums\MatterKind::cases() as $kind)
                <option value="{{ $kind->value }}" data-description="{{ $kind->description() }}" @selected($selectedKind === $kind->value)>{{ $kind->label() }}</option>
            @endforeach
        </select>
        <div class="form-text" id="vrstaOpis"></div>
    </div>
    <div class="col-md-3 mb-3"><label class="form-label">Status</label>
        <select name="status" id="statusPredmeta" class="form-select">
            @foreach(\App\Enums\MatterStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('status', $matter->status?->value ?? 'active') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Predmet spora</label>
        <select name="dispute_category_id" id="predmetSpora" class="form-select">
            <option value="">—</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" data-kind="{{ $category->kind->value }}" data-hint="{{ $category->hint }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <div class="form-text" id="predmetSporaHint"></div>
    </div>
    <div class="col-md-6 mb-3"><label class="form-label">Pozicija ureda</label>
        <select name="office_position" class="form-select" required>
            <option value="">Odaberite</option>
            @foreach(\App\Enums\OfficePosition::cases() as $position)
                <option value="{{ $position->value }}" @selected(old('office_position', $matter->office_position?->value) === $position->value)>{{ $position->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Sud</label>
        <select name="court_id" id="sudPredmeta" class="form-select">
            <option value="">—</option>
            @foreach($courtGroups as $label => $group)
                <optgroup label="{{ $label }}">
                    @foreach($group as $court)
                        <option value="{{ $court->id }}" @selected((string) $selectedCourt === (string) $court->id)>{{ $court->name }}</option>
                    @endforeach
                </optgroup>
            @endforeach
            <option value="other" @selected($selectedCourt === 'other')>Nije na popisu</option>
        </select>
    </div>
    <div class="col-md-6 mb-3"><label class="form-label">Broj predmeta</label>
        <input name="court_case_number" class="form-control" value="{{ old('court_case_number', $matter->court_case_number) }}" placeholder="P-123/2026">
        <div class="form-text">Sudski broj za e-Spis, npr. P-123/2026 ili K-45/2026.</div>
    </div>
</div>
<div class="mb-3" id="drugiSud" @if($selectedCourt !== 'other') hidden @endif>
    <label class="form-label">Naziv suda</label>
    <input name="court_name" class="form-control" value="{{ old('court_name', $matter->court_id ? '' : $matter->court_name) }}">
</div>
<div class="mb-3" id="ishodPredmeta" @if(old('status', $matter->status?->value) !== 'archived') hidden @endif>
    <label class="form-label">Ishod</label>
    <select name="outcome" class="form-select">
        <option value="">Odaberite ishod</option>
        @foreach(\App\Enums\MatterOutcome::cases() as $outcome)
            <option value="{{ $outcome->value }}" @selected(old('outcome', $matter->outcome?->value) === $outcome->value)>{{ $outcome->label() }}</option>
        @endforeach
    </select>
</div>
<div class="row">
    <div class="col-md-3 mb-3"><label class="form-label">Vrijednost spora (EUR)</label><input name="dispute_value" class="form-control" value="{{ old('dispute_value', $matter->dispute_value_cents ? number_format($matter->dispute_value_cents/100, 2, '.', '') : '') }}"></div>
    <div class="col-md-3 mb-3"><label class="form-label">Naplata</label>
        <select name="billing_method" class="form-select">
            @foreach(\App\Enums\BillingMethod::cases() as $method)
                <option value="{{ $method->value }}" @selected(old('billing_method', $matter->billing_method?->value) === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3"><label class="form-label">Satnica (EUR)</label><input name="hourly_rate" class="form-control" value="{{ old('hourly_rate', $matter->hourly_rate_cents ? number_format($matter->hourly_rate_cents/100, 2, '.', '') : '') }}"></div>
    <div class="col-md-3 mb-3"><label class="form-label">Paušal (EUR)</label><input name="flat_fee" class="form-control" value="{{ old('flat_fee', $matter->flat_fee_cents ? number_format($matter->flat_fee_cents/100, 2, '.', '') : '') }}"></div>
</div>
<div class="mb-3"><label class="form-label">Napomena o uspjehu u sporu</label><textarea name="success_fee_note" class="form-control">{{ old('success_fee_note', $matter->success_fee_note) }}</textarea></div>
<div class="mb-3">
    <label class="form-label">Dodijeljeni</label>
    <select name="assignee_ids[]" class="form-select" multiple>
        @foreach($lawyers as $lawyer)
            <option value="{{ $lawyer->user_id }}" @selected(collect(old('assignee_ids', $matter->exists ? $matter->assignees->pluck('id')->all() : []))->contains($lawyer->user_id))>{{ $lawyer->user->name }} ({{ $lawyer->role->label() }})</option>
        @endforeach
    </select>
</div>
<script>
    (function () {
        const kind = document.getElementById('vrstaPredmeta');
        const category = document.getElementById('predmetSpora');
        const hint = document.getElementById('predmetSporaHint');
        const description = document.getElementById('vrstaOpis');
        const court = document.getElementById('sudPredmeta');
        const other = document.getElementById('drugiSud');
        const status = document.getElementById('statusPredmeta');
        const outcome = document.getElementById('ishodPredmeta');

        function syncKind() {
            const value = kind.value;
            description.textContent = kind.selectedOptions[0]?.dataset.description || '';
            let selectedStillVisible = false;
            Array.from(category.options).forEach((option) => {
                if (!option.value) return;
                const visible = option.dataset.kind === value;
                option.hidden = !visible;
                option.disabled = !visible;
                if (visible && option.value === category.value) selectedStillVisible = true;
            });
            if (category.value && !selectedStillVisible) category.value = '';
            syncHint();
        }

        function syncHint() {
            hint.textContent = category.selectedOptions[0]?.dataset.hint || '';
        }

        function syncCourt() {
            other.hidden = court.value !== 'other';
        }

        function syncStatus() {
            outcome.hidden = status.value !== 'archived';
        }

        kind.addEventListener('change', syncKind);
        category.addEventListener('change', syncHint);
        court.addEventListener('change', syncCourt);
        status.addEventListener('change', syncStatus);
        syncKind();
        syncCourt();
        syncStatus();
    })();
</script>
