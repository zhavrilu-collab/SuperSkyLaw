@extends('layouts.app')
@section('title', 'Pošta')
@section('nav-suffix', 'Pošta')
@section('content')
<h1 class="h5 text-tema mb-3">Pošta u predmet</h1>
<div class="kartica-kontejner mb-3">
    <p>Proslijedite poruku na ovu adresu sučelja. U predmetu ili tijelu mora biti interni broj, na primjer <strong>[2026/001]</strong>.</p>
    <code>{{ url('/api/posta/'.$token) }}</code>
</div>
<form method="POST" action="{{ route('organization.mail.store', $org->slug) }}" class="kartica-kontejner">
    @csrf
    <div class="mb-2"><input name="from" type="email" class="form-control" placeholder="Pošiljatelj" required></div>
    <div class="mb-2"><input name="subject" class="form-control" placeholder="Predmet, npr. Dopis [2026/001]" required></div>
    <div class="mb-2"><textarea name="body" class="form-control" rows="6" placeholder="Tekst poruke" required></textarea></div>
    <button class="btn btn-primary btn-sm" type="submit">Spremi u predmet</button>
</form>
@endsection
