@extends('layouts.app')
@section('title', 'Stranka')
@section('nav-suffix', 'Stranke')
@section('content')
<h1 class="h5 text-tema mb-3">{{ $party->exists ? 'Uredi stranku' : 'Nova stranka' }}</h1>
<form method="POST" action="{{ $party->exists ? route('organization.parties.update', [$org->slug, $party->id]) : route('organization.parties.store', $org->slug) }}" class="kartica-kontejner">
    @csrf
    @if($party->exists) @method('PUT') @endif
    <div class="row">
        <div class="col-md-4 mb-3"><label class="form-label">Vrsta</label>
            <select name="kind" class="form-select">
                @foreach(\App\Enums\PartyKind::cases() as $kind)
                    <option value="{{ $kind->value }}" @selected(old('kind', $party->kind?->value) === $kind->value)>{{ $kind->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8 mb-3"><label class="form-label">Naziv</label><input name="name" class="form-control" required value="{{ old('name', $party->name) }}"></div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3"><label class="form-label">OIB</label><input name="oib" class="form-control" value="{{ old('oib', $party->oib) }}"></div>
        <div class="col-md-4 mb-3"><label class="form-label">MBS</label><input name="mbs" class="form-control" value="{{ old('mbs', $party->mbs) }}"></div>
        <div class="col-md-4 mb-3"><label class="form-label">Kontakt osoba</label><input name="contact_person" class="form-control" value="{{ old('contact_person', $party->contact_person) }}"></div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Adresa</label><input name="address" class="form-control" value="{{ old('address', $party->address) }}"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Grad</label><input name="city" class="form-control" value="{{ old('city', $party->city) }}"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Telefon</label><input name="phone" class="form-control" value="{{ old('phone', $party->phone) }}"></div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">E-mail</label><input name="email" class="form-control" value="{{ old('email', $party->email) }}"></div>
        <div class="col-md-6 mb-3"><label class="form-label">IBAN</label><input name="iban" class="form-control" value="{{ old('iban', $party->iban) }}"></div>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="sms_consent" value="1" id="sms-consent" @checked(old('sms_consent', $party->sms_consent_at !== null))>
        <label class="form-check-label" for="sms-consent">Privola za SMS. Bez privole se poruka ne šalje i ne naplaćuje.</label>
    </div>
    <button class="btn btn-primary" type="submit">Spremi</button>
</form>
@if($party->exists && $party->kind?->value === 'company')
<form method="POST" action="{{ route('organization.parties.registry', [$org->slug, $party->id]) }}" class="kartica-kontejner mt-3">
    @csrf
    <h2 class="h6 text-tema">Sudski registar</h2>
    <p class="text-muted">Dohvat po OIB-u ili MBS-u. Osobni OIB se ne traži.</p>
    <button class="btn btn-outline-success btn-sm" type="submit">Dohvati podatke tvrtke</button>
</form>
@endif
@if($party->exists)
@planFeature('client_portal')
<form method="POST" action="{{ route('organization.parties.portal', [$org->slug, $party->id]) }}" class="kartica-kontejner mt-3">
    @csrf
    <h2 class="h6 text-tema">Portal klijenta</h2>
    <div class="row">
        <div class="col-md-6 mb-2"><input name="email" type="email" class="form-control" placeholder="E-mail za prijavu" value="{{ old('email', $party->email) }}" required></div>
        <div class="col-md-4 mb-2"><input name="password" type="text" class="form-control" placeholder="Lozinka, najmanje 8 znakova" required></div>
        <div class="col-md-2 mb-2"><button class="btn btn-primary" type="submit">Otvori</button></div>
    </div>
</form>
@endplanFeature
@endif
@endsection
