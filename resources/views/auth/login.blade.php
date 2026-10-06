@extends('layouts.guest-login')

@section('title', 'Prijava — SuperSkyLaw')

@section('tagline', 'Platforma za upravljanje odvjetničkim društvima.')

@section('content')
@if (session('status') || session('success'))
    <div class="alert alert-success small">{{ session('status') ?: session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-danger small">{{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Lozinka</label>
        <input id="password" type="password" name="password"
               class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember_me" name="remember">
        <label class="form-check-label" for="remember_me">Zapamti me</label>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-login">Prijavi se</button>
    </div>
</form>

@if(!empty($googleLoginUrl) || !empty($microsoftLoginUrl))
    <div class="d-grid gap-2 mt-3">
        @if(!empty($googleLoginUrl))
            <a href="{{ $googleLoginUrl }}" class="btn btn-outline-secondary">Prijava s Googleom</a>
        @endif
        @if(!empty($microsoftLoginUrl))
            <a href="{{ $microsoftLoginUrl }}" class="btn btn-outline-secondary">Prijava s Microsoftom</a>
        @endif
    </div>
@endif
@endsection
