@extends('layouts.app')
@section('title', 'Obavijesti')
@section('nav-suffix', 'Obavijesti')
@section('content')
<h1 class="h5 text-tema mb-3">Obavijesti</h1>
<div class="kartica-kontejner">
    @forelse($notifications as $notification)
        <div class="border-bottom py-2">
            <div class="fw-semibold">{{ $notification->title }}</div>
            <div>{{ $notification->body }}</div>
            <div class="text-muted">{{ $notification->created_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</div>
        </div>
    @empty
        <p class="text-muted mb-0">Nema obavijesti.</p>
    @endforelse
</div>
@endsection
