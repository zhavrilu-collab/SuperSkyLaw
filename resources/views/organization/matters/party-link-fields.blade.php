<div class="unos stranka-polja">
    <input name="links[{{ $veza->id }}][name]" class="form-control form-control-sm mb-1" value="{{ old('links.'.$veza->id.'.name', $veza->party->name) }}" aria-label="Naziv stranke">
    <input name="links[{{ $veza->id }}][oib]" class="form-control form-control-sm mb-1" value="{{ old('links.'.$veza->id.'.oib', $veza->party->oib) }}" aria-label="OIB">
    <select name="links[{{ $veza->id }}][kind]" class="form-select form-select-sm mb-1" aria-label="Vrsta osobe">
        @foreach(\App\Enums\PartyKind::cases() as $kind)
            <option value="{{ $kind->value }}" @selected(old('links.'.$veza->id.'.kind', $veza->party->kind?->value) === $kind->value)>{{ $kind->label() }}</option>
        @endforeach
    </select>
    <select name="links[{{ $veza->id }}][role]" class="form-select form-select-sm mb-1" aria-label="Uloga">
        @foreach(\App\Enums\MatterPartyRole::cases() as $role)
            <option value="{{ $role->value }}" @selected(old('links.'.$veza->id.'.role', $veza->role?->value) === $role->value)>{{ $role->label() }}</option>
        @endforeach
    </select>
    <select name="links[{{ $veza->id }}][side]" class="form-select form-select-sm" aria-label="Strana">
        @foreach(\App\Enums\PartySide::cases() as $side)
            <option value="{{ $side->value }}" @selected(old('links.'.$veza->id.'.side', $veza->side?->value) === $side->value)>{{ $side->label() }}</option>
        @endforeach
    </select>
</div>
