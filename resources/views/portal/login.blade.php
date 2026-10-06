@extends('layouts.guest')
@section('title', 'Portal — '.$organization->name)
@section('content')
<div class="card">
    <div class="card-body p-4">
        <h1 class="h4 text-tema mb-1">Portal klijenta</h1>
        <p class="text-muted">{{ $organization->name }}</p>
        <form method="POST" action="{{ route('portal.login', $organization->slug) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Lozinka</label>
                <input id="password" type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary" type="submit">Prijava</button>
        </form>
    </div>
</div>
@endsection
