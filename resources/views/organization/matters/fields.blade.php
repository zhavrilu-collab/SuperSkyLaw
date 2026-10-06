<div class="row">
    <div class="col-md-6 mb-3"><label class="form-label">Naziv</label><input name="title" class="form-control" value="{{ old('title', $matter->title) }}" required></div>
    <div class="col-md-3 mb-3"><label class="form-label">Vrsta</label>
        <select name="kind" class="form-select">
            @foreach(\App\Enums\MatterKind::cases() as $kind)
                <option value="{{ $kind->value }}" @selected(old('kind', $matter->kind?->value) === $kind->value)>{{ $kind->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3"><label class="form-label">Status</label>
        <select name="status" class="form-select">
            @foreach(\App\Enums\MatterStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('status', $matter->status?->value ?? 'active') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-3"><label class="form-label">Sud</label><input name="court_name" class="form-control" value="{{ old('court_name', $matter->court_name) }}"></div>
    <div class="col-md-2 mb-3"><label class="form-label">Oznaka</label><input name="case_mark" class="form-control" value="{{ old('case_mark', $matter->case_mark) }}" placeholder="P"></div>
    <div class="col-md-3 mb-3"><label class="form-label">Broj</label><input name="case_number" class="form-control" value="{{ old('case_number', $matter->case_number) }}"></div>
    <div class="col-md-3 mb-3"><label class="form-label">Godina</label><input name="case_year" class="form-control" value="{{ old('case_year', $matter->case_year) }}"></div>
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
