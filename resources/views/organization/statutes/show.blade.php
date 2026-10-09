@extends('layouts.app')
@section('title', $statute->citation)
@section('nav-suffix', 'Biblioteka')
@section('content')
<p class="mb-2"><a href="{{ route('organization.statutes.index', $org->slug) }}">Biblioteka</a></p>
<h1 class="h5 text-tema mb-1">{{ $statute->title }}</h1>
<p class="text-muted">{{ $statute->citation }}@if($statute->published_on) · {{ $statute->published_on->format('d.m.Y.') }}@endif</p>
<p class="small text-muted">Službeni tekst objave u Narodnim novinama, ne redakcijski pročišćeni tekst. <a href="{{ $statute->source_url }}" target="_blank" rel="noopener">Izvornik</a></p>
<div class="kartica-kontejner">
    @if($statute->text_html)
        {!! $statute->text_html !!}
    @else
        <p class="text-muted mb-0">Tekst još nije preuzet. Ostaje poveznica na Narodne novine.</p>
    @endif
</div>
@endsection
