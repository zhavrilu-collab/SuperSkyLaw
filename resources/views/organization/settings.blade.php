@extends('layouts.app')
@section('title', 'Ured')
@section('nav-suffix', 'Ured')
@section('content')
<h1 class="h5 text-tema mb-3">Osnovni podaci ureda</h1>
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

@php($activeTheme = \App\Support\OfficeThemes::resolve(old('theme_color', $organization->theme_color)))
@php($catalog = \App\Support\OfficeThemes::all())
<form method="POST" action="{{ route('organization.settings.theme', $organization->slug) }}" id="formTemaUreda" class="kartica-kontejner mt-3">
    @csrf
    @method('PUT')
    <span class="fw-bold text-muted small d-block mb-2">BOJA TEME</span>
    <input type="hidden" name="theme_color" id="themeColorInput" value="{{ $activeTheme }}">
    <img id="temaLogoPregled"
         src="{{ asset(\App\Support\OfficeThemes::horizontalPath($activeTheme)) }}"
         alt="SuperSkyLaw"
         class="tema-logo-pregled">
    <div class="mb-2">
        <label class="form-label small fw-bold mb-2">Službena boja</label>
        <div class="d-flex gap-2 mb-2 flex-wrap align-items-center" id="temaBojaIzbor">
            @foreach($catalog as $key => $theme)
                <button type="button"
                        class="tema-svatch @if($key === $activeTheme) aktivna @endif"
                        data-tema="{{ $key }}"
                        style="background:{{ $theme['primary'] }};"
                        title="{{ $theme['label'] }}"
                        aria-label="{{ $theme['label'] }}"></button>
            @endforeach
        </div>
        <div class="form-text">Zelena je zadana. Odabirom boje odmah se vidi službeni prozirni logo.</div>
    </div>
    @error('theme_color')
        <div class="text-danger small mb-2">{{ $message }}</div>
    @enderror
    <button type="submit" class="btn btn-success btn-sm btn-spremi">Spremi temu</button>
    <span id="temaPoruka" class="ms-2 small text-muted"></span>
</form>
@endsection

@push('scripts')
<script>
window.THEME_PREVIEW = {
    savedTheme: @json($activeTheme),
    palettes: @json(\App\Support\OfficeThemes::previewPayload()),
};
</script>
<script src="{{ asset('js/theme-preview.js') }}"></script>
@endpush
