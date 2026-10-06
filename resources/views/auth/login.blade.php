@extends('layouts.guest')
@section('title', 'Prijava — SuperSkyLaw')
@section('content')
<div class="card">
    <div class="card-body p-4">
        <h1 class="h5 text-tema mb-2">Prijava</h1>
        <p class="text-muted small">Prijava u odvjetnički ured.</p>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3"><label class="form-label" for="email">E-mail</label><input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus></div>
            <div class="mb-3"><label class="form-label" for="password">Lozinka</label><input type="password" name="password" id="password" class="form-control" required></div>
            <button type="submit" class="btn btn-primary w-100">Prijava</button>
        </form>
        @if($googleLoginUrl || $microsoftLoginUrl)
            <hr>
            <div class="d-grid gap-2">
                @if($googleLoginUrl)<a href="{{ $googleLoginUrl }}" class="btn btn-outline-success btn-sm">Prijava s Googleom</a>@endif
                @if($microsoftLoginUrl)<a href="{{ $microsoftLoginUrl }}" class="btn btn-outline-success btn-sm">Prijava s Microsoftom</a>@endif
            </div>
        @endif
    </div>
</div>
@endsection
