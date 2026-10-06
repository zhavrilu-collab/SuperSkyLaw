@extends('layouts.app')
@section('title', 'Predlošci')
@section('nav-suffix', 'Predlošci')
@section('content')
<h1 class="h5 text-tema mb-3">Predlošci</h1>
@perm('documents.manage')
<form method="POST" action="{{ route('organization.templates.generate', $org->slug) }}" class="kartica-kontejner mb-3">
    @csrf
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">Predložak</label><select name="template_id" class="form-select" required>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Predmet</label><select name="matter_id" class="form-select" required>@foreach($matters as $matter)<option value="{{ $matter->id }}">{{ $matter->internal_number }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Stranka</label><select name="party_id" class="form-select" required>@foreach($parties as $party)<option value="{{ $party->id }}">{{ $party->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary" type="submit">Izradi</button></div>
    </div>
</form>
@endperm
@foreach($templates as $template)
<div class="kartica-kontejner mb-3">
    <h2 class="h6 text-tema">{{ $template->name }}</h2>
    <pre class="mb-0 small" style="white-space: pre-wrap;">{{ $template->body }}</pre>
</div>
@endforeach
@endsection
