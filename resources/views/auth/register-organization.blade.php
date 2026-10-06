@extends('layouts.guest-login')

@section('title', 'Registracija ureda — SuperSkyLaw')
@section('tagline', 'Platforma za upravljanje odvjetničkim društvima.')
@section('shell-width', 'col-lg-7')

@section('content')
<h1 class="h4 mb-2">Registracija ureda</h1>
<p class="text-muted small">Nakon registracije ured čeka odobrenje super-administratora.</p>

@if ($errors->any())
    <div class="alert alert-danger small">
        <strong>Registracija nije spremljena.</strong> Ispravite označena polja.
    </div>
@endif

<form method="POST" action="{{ route('register.organization') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label" for="name">Naziv ureda</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label" for="oib">OIB</label>
            <input type="text" name="oib" id="oib" class="form-control @error('oib') is-invalid @enderror" value="{{ old('oib') }}" required>
            @error('oib')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label" for="organization_email">E-mail ureda</label>
            <input type="email" name="organization_email" id="organization_email" class="form-control @error('organization_email') is-invalid @enderror" value="{{ old('organization_email') }}" required>
            @error('organization_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label" for="phone">Telefon</label>
            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label" for="city">Grad</label>
            <input type="text" name="city" id="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}">
            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    @unless($isLoggedIn)
        <hr>
        <h2 class="h6">Partnerski račun</h2>
        <div class="mb-3">
            <label class="form-label" for="admin_name">Ime i prezime</label>
            <input type="text" name="admin_name" id="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" required>
            @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="admin_email">E-mail</label>
            <input type="email" name="admin_email" id="admin_email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email') }}">
            @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Lozinka</label>
            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirmation">Potvrda lozinke</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
        </div>
    @endunless
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <a href="{{ route('login') }}">Natrag na prijavu</a>
        <button type="submit" class="btn btn-login">Registriraj ured</button>
    </div>
</form>
@endsection
