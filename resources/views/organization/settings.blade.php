@extends('layouts.app')
@section('title', 'Ured')
@section('nav-suffix', 'Ured')
@section('content')
<h1 class="h5 text-tema mb-3">Ured</h1>
<h2 class="h6 text-tema">Osnovni podaci</h2>
<form method="POST" action="{{ route('organization.settings.update', $org->slug) }}" class="kartica-kontejner">
    @csrf @method('PUT')
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Naziv</label><input name="name" class="form-control" value="{{ old('name', $organization->name) }}" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">OIB</label><input class="form-control" value="{{ $organization->oib }}" disabled></div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">E-mail</label><input name="email" class="form-control" value="{{ old('email', $organization->email) }}" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">Telefon</label><input name="phone" class="form-control" value="{{ old('phone', $organization->phone) }}"></div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Adresa</label><input name="address" class="form-control" value="{{ old('address', $organization->address) }}"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Grad</label><input name="city" class="form-control" value="{{ old('city', $organization->city) }}"></div>
        <div class="col-md-3 mb-3"><label class="form-label">IBAN</label><input name="iban" class="form-control" value="{{ old('iban', $organization->iban) }}"></div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">IBAN depozitnog računa</label><input name="trust_iban" class="form-control" value="{{ old('trust_iban', $organization->trust_iban) }}"><div class="form-text">Odvojen od poslovnog IBAN-a. Sredstva stranaka ne ulaze u prihod ureda.</div></div>
    </div>
    <button class="btn btn-primary" type="submit">Spremi</button>
</form>

@perm('team.manage')
<div class="mt-4">
    @include('organization.partials.team-panel')
</div>
@endperm
@endsection
