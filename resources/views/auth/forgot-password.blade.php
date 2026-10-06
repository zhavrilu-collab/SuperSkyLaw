@extends('layouts.guest-login')

@section('title', 'Zaboravljena lozinka — SuperSkyLaw')

@section('tagline', 'Platforma za upravljanje odvjetničkim društvima.')

@section('content')
<p class="text-muted small mb-4">Unesite e-mail adresu s kojom ste se registrirali. Poslat ćemo vam poveznicu za novu lozinku.</p>

@if (session('status'))
    <div class="alert alert-success small">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-login">Pošalji poveznicu</button>
    </div>
</form>

<div class="login-links">
    <p class="text-center small">
        <a href="{{ route('login') }}">Natrag na prijavu</a>
    </p>
    <p class="text-center small"></p>
</div>
@endsection
