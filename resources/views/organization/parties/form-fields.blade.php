@php $canManage = $canManage ?? true; @endphp
<form method="POST" action="{{ $party->exists ? route('organization.parties.update', [$org->slug, $party->id]) : route('organization.parties.store', $org->slug) }}" class="kartica-kontejner">
    @csrf
    @if($party->exists) @method('PUT') @endif
    <div class="row">
        <div class="col-md-4 mb-3"><label class="form-label">Vrsta</label>
            <select name="kind" class="form-select" @disabled(! $canManage)>
                @foreach(\App\Enums\PartyKind::cases() as $kind)
                    <option value="{{ $kind->value }}" @selected(old('kind', $party->kind?->value) === $kind->value)>{{ $kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8 mb-3"><label class="form-label" for="nazivStranke">Naziv</label><input name="name" id="nazivStranke" class="form-control" required value="{{ old('name', $party->name) }}" @disabled(! $canManage)></div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3"><label class="form-label">OIB</label><input name="oib" class="form-control" value="{{ old('oib', $party->oib) }}" @disabled(! $canManage)></div>
        <div class="col-md-4 mb-3"><label class="form-label">MBS</label><input name="mbs" class="form-control" value="{{ old('mbs', $party->mbs) }}" @disabled(! $canManage)></div>
        <div class="col-md-4 mb-3"><label class="form-label">Kontakt osoba</label><input name="contact_person" class="form-control" value="{{ old('contact_person', $party->contact_person) }}" @disabled(! $canManage)></div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Adresa</label><input name="address" class="form-control" value="{{ old('address', $party->address) }}" @disabled(! $canManage)></div>
        <div class="col-md-3 mb-3"><label class="form-label">Grad</label><input name="city" class="form-control" value="{{ old('city', $party->city) }}" @disabled(! $canManage)></div>
        <div class="col-md-3 mb-3"><label class="form-label">Telefon</label><input name="phone" class="form-control" value="{{ old('phone', $party->phone) }}" @disabled(! $canManage)></div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">E-mail</label><input name="email" class="form-control" value="{{ old('email', $party->email) }}" @disabled(! $canManage)></div>
        <div class="col-md-6 mb-3"><label class="form-label">IBAN</label><input name="iban" class="form-control" value="{{ old('iban', $party->iban) }}" @disabled(! $canManage)></div>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="sms_consent" value="1" id="sms-consent" @checked(old('sms_consent', $party->sms_consent_at !== null)) @disabled(! $canManage)>
        <label class="form-check-label" for="sms-consent">Privola za SMS. Bez privole se poruka ne šalje i ne naplaćuje.</label>
    </div>
    @perm('parties.manage')
    <button class="btn btn-primary btn-sm" type="submit">Spremi</button>
    @endperm
</form>
@if($party->exists && $party->kind?->value === 'company')
@perm('parties.manage')
<form method="POST" action="{{ route('organization.parties.registry', [$org->slug, $party->id]) }}" class="kartica-kontejner mt-3">
    @csrf
    <h2 class="h6 text-tema">Sudski registar</h2>
    <p class="text-muted">Dohvat po OIB-u ili MBS-u. Osobni OIB se ne traži.</p>
    <button class="btn btn-outline-success btn-sm" type="submit">Dohvati podatke tvrtke</button>
</form>
@endperm
@endif
@if($party->exists)
@planFeature('client_portal')
@perm('parties.manage')
<form method="POST" action="{{ route('organization.parties.portal', [$org->slug, $party->id]) }}" class="kartica-kontejner mt-3">
    @csrf
    <h2 class="h6 text-tema">Portal klijenta</h2>
    <div class="row">
        <div class="col-md-6 mb-2"><label class="form-label" for="portalEmail">E-mail za prijavu</label><input name="email" id="portalEmail" type="email" class="form-control" value="{{ old('email', $party->email) }}" required></div>
        <div class="col-md-4 mb-2"><label class="form-label" for="portalLozinka">Lozinka</label><input name="password" id="portalLozinka" type="text" class="form-control" required></div>
        <div class="col-md-2 mb-2 d-flex align-items-end"><button class="btn btn-primary btn-sm" type="submit">Otvori</button></div>
    </div>
    <div class="form-text">Lozinka ima najmanje 8 znakova.</div>
</form>
@endperm
@endplanFeature
@endif
