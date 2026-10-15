@extends('layouts.app')
@section('title', 'Dokumenti')
@section('nav-suffix', 'Dokumenti')
@section('content')
<h1 class="h5 text-tema mb-3">Dokumenti</h1>
@include('organization.document-areas')
@perm('documents.manage')
<form method="POST" action="{{ route('organization.templates.generate', $org->slug) }}" class="kartica-kontejner mb-3">
    @csrf
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">Predložak</label><select name="template_id" class="form-select" required>@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Predmet</label><select name="matter_id" class="form-select" required>@foreach($matters as $matter)<option value="{{ $matter->id }}">{{ $matter->internal_number }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Stranka</label><select name="party_id" class="form-select" required>@foreach($parties as $party)<option value="{{ $party->id }}">{{ $party->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><x-ikona znak="check" label="Izradi" /></div>
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
