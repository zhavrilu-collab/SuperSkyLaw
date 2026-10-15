@extends('layouts.app')
@section('title', 'Postavke')
@section('nav-suffix', 'Postavke')
@section('content')
<h1 class="h5 text-tema mb-3">Postavke</h1>
@include('organization.settings-areas')
<div class="kartica-kontejner">
    <h2 class="h6 text-tema">Vanjski kalendar</h2>
    <p class="mb-2"><a href="{{ route('organization.calendar.export', $organization->slug) }}">Preuzmi ICS</a></p>
    <p class="small text-muted">Google ili Outlook mogu se pretplatiti na: {{ route('calendar.feed', $organization->feedToken('calendar_feed_token')) }}</p>
    @perm('calendar.manage')
    <form method="POST" action="{{ route('organization.calendar.import', $organization->slug) }}" class="row g-2">
        @csrf
        <div class="col-md-8">
            <label class="form-label" for="feedUrl">Adresa vanjskog ICS feeda</label>
            <input name="feed_url" id="feedUrl" type="url" class="form-control form-control-sm" placeholder="https://" required>
        </div>
        <div class="col-md-4 d-flex align-items-end"><x-ikona znak="download" label="Uvezi" /></div>
    </form>
    @endperm
</div>
@endsection
