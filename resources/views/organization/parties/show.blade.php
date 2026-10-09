@extends('layouts.app')
@section('title', $party->name)
@section('nav-suffix', 'Stranke')
@push('styles')
<style>
    .predmet-tabovi { border-bottom: 1px solid rgba(var(--tema-rgb), .22); gap: 4px; }
    .predmet-tabovi .nav-link { color: #5c6540; font-weight: 700; border: 0; border-bottom: 2px solid transparent; border-radius: 0; margin-bottom: -1px; }
    .predmet-tabovi .nav-link.active { color: var(--primarna-tamna); background: transparent; border-bottom-color: var(--primarna-zelena); }
</style>
@endpush
@section('content')
<h1 class="h5 text-tema mb-3">{{ $party->name }}</h1>
<ul class="nav predmet-tabovi mb-3">
    @foreach(['podaci' => 'Podaci', 'dokumenti' => 'Dokumenti', 'predmeti' => 'Predmeti', 'racuni' => 'Računi'] as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('organization.parties.show', [$org->slug, $party->id, 'tab' => $key]) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>
@include('organization.parties.tabs.'.$tab)
@endsection
