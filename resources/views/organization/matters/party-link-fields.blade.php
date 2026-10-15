@php
    $urediOvu = (string) old('_link') === (string) $veza->id;
    $odabranaStranka = (string) ($urediOvu ? old('party_id', $veza->party_id) : $veza->party_id);
    $odabranaUloga = (string) ($urediOvu ? old('role', $veza->role?->value) : $veza->role?->value);
    $odabranaStrana = (string) ($urediOvu ? old('side', $veza->side?->value) : $veza->side?->value);
@endphp
<dd class="stranka-red {{ $urediOvu ? 'ureduje' : '' }}">
    <div class="stranka-prikaz">
        <span>{{ $opis ?: '—' }}</span>
        @perm('matters.manage')
        <span class="stranka-akcije">
            <button type="button" class="ikona uredi-stranku" title="Uredi stranku" aria-label="Uredi stranku">
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
            </button>
            <button type="submit" class="ikona opasno" form="brisiStranku{{ $veza->id }}" title="Ukloni stranku s predmeta" aria-label="Ukloni stranku s predmeta">
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3h11V2h-11v1z"/></svg>
            </button>
        </span>
        @endperm
    </div>
    @perm('matters.manage')
    <div class="stranka-unos">
        <input form="urediStranku{{ $veza->id }}" type="hidden" name="_link" value="{{ $veza->id }}">
        <label class="form-label" for="strankaVeze{{ $veza->id }}">Stranka</label>
        <select form="urediStranku{{ $veza->id }}" name="party_id" id="strankaVeze{{ $veza->id }}" class="form-select form-select-sm mb-1" required>
            @foreach($parties as $party)
                <option value="{{ $party->id }}" @selected($odabranaStranka === (string) $party->id)>{{ $party->name }}</option>
            @endforeach
        </select>
        <label class="form-label" for="ulogaVeze{{ $veza->id }}">Uloga</label>
        <select form="urediStranku{{ $veza->id }}" name="role" id="ulogaVeze{{ $veza->id }}" class="form-select form-select-sm mb-1">
            @foreach(\App\Enums\MatterPartyRole::cases() as $role)
                <option value="{{ $role->value }}" @selected($odabranaUloga === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
        <label class="form-label" for="stranaVeze{{ $veza->id }}">Strana</label>
        <select form="urediStranku{{ $veza->id }}" name="side" id="stranaVeze{{ $veza->id }}" class="form-select form-select-sm mb-1">
            @foreach(\App\Enums\PartySide::cases() as $side)
                <option value="{{ $side->value }}" @selected($odabranaStrana === $side->value)>{{ $side->label() }}</option>
            @endforeach
        </select>
        <div class="form-check mb-2">
            <input form="urediStranku{{ $veza->id }}" class="form-check-input" type="checkbox" name="acknowledge_conflict" value="1" id="sukobVeze{{ $veza->id }}">
            <label class="form-check-label" for="sukobVeze{{ $veza->id }}">Potvrđujem provjeru sukoba</label>
        </div>
        @if($urediOvu)
            @error('party_id')<p class="text-danger small mb-2">{{ $message }}</p>@enderror
        @endif
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm odustani-stranku">Odustani</button>
            <button class="btn btn-primary btn-sm" type="submit" form="urediStranku{{ $veza->id }}">Spremi</button>
        </div>
    </div>
    @endperm
</dd>
