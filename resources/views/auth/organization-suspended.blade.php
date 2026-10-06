@extends('layouts.guest')
@section('title', 'Pristup suspendiran — SuperSkyLaw')
@section('content')
<div class="card"><div class="card-body p-4 text-center">
    <h1 class="h5 text-tema mb-3">Pristup suspendiran</h1>
    <p class="text-muted">Pristup uredu <strong>{{ $organization->name }}</strong> je privremeno onemogućen.</p>
    <a href="{{ route('organization.pick') }}" class="btn btn-outline-success">Odaberi drugi ured</a>
    <form method="POST" action="{{ route('logout') }}" class="d-inline">@csrf<button type="submit" class="btn btn-primary">Odjava</button></form>
</div></div>
@endsection
