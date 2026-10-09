@extends('layouts.app')
@section('title', 'Katalog')
@section('nav-suffix', 'Postavke')
@section('content')
<h1 class="h5 text-tema mb-1">Katalog</h1>
<p class="text-muted mb-3">Predložak stadija i predmeti spora za ovaj ured. Vrste predmeta ostaju iste. Promjena predloška ne dira stadije koji su već na predmetima.</p>
@if(session('status'))<p class="alert alert-warning py-2">{{ session('status') }}</p>@endif
@if($errors->has('category'))<p class="text-danger small">{{ $errors->first('category') }}</p>@endif
@foreach($kinds as $kind)
    <div class="kartica-kontejner mb-3">
        <h2 class="h6 text-tema mb-1">{{ $kind->label() }}</h2>
        <p class="small text-muted mb-3">{{ $kind->description() }}</p>
        <div class="row g-4">
            <div class="col-lg-6">
                <h3 class="h6">Stadiji</h3>
                @foreach($templates[$kind->value] ?? [] as $template)
                    <form method="POST" action="{{ route('organization.settings.catalog.stages.update', [$organization->slug, $template->id]) }}" class="d-flex gap-2 align-items-end mb-2">
                        @csrf @method('PUT')
                        <div class="flex-grow-1">
                            <label class="form-label" for="stadij{{ $template->id }}">Naziv</label>
                            <input name="name" id="stadij{{ $template->id }}" class="form-control" required value="{{ $template->name }}">
                        </div>
                        <div style="width:72px">
                            <label class="form-label" for="red{{ $template->id }}">Red</label>
                            <input type="number" name="position" id="red{{ $template->id }}" class="form-control" min="1" max="50" required value="{{ $template->position }}">
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit">Spremi</button>
                    </form>
                    <form method="POST" action="{{ route('organization.settings.catalog.stages.destroy', [$organization->slug, $template->id]) }}" class="mb-3">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm" type="submit">Ukloni iz predloška</button>
                    </form>
                @endforeach
                <form method="POST" action="{{ route('organization.settings.catalog.stages.store', $organization->slug) }}" class="d-flex gap-2 align-items-end">
                    @csrf
                    <input type="hidden" name="kind" value="{{ $kind->value }}">
                    <div class="flex-grow-1">
                        <label class="form-label" for="noviStadij{{ $kind->value }}">Novi stadij</label>
                        <input name="name" id="noviStadij{{ $kind->value }}" class="form-control" required maxlength="80">
                    </div>
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Dodaj</button>
                </form>
            </div>
            <div class="col-lg-6">
                <h3 class="h6">Predmeti spora</h3>
                @foreach($categories[$kind->value] ?? [] as $category)
                    <form method="POST" action="{{ route('organization.settings.catalog.categories.update', [$organization->slug, $category->id]) }}" class="mb-2">
                        @csrf @method('PUT')
                        <label class="form-label" for="spor{{ $category->id }}">Naziv</label>
                        <input name="name" id="spor{{ $category->id }}" class="form-control mb-1" required maxlength="160" value="{{ $category->name }}">
                        @if(in_array($category->name, $statutoryNames, true))
                            <p class="small text-muted mb-1">Zakonski rok traži točno ovaj naziv. Ako ga promijenite, rok više neće iskociti.</p>
                        @endif
                        <button class="btn btn-primary btn-sm" type="submit">Spremi</button>
                    </form>
                    <form method="POST" action="{{ route('organization.settings.catalog.categories.destroy', [$organization->slug, $category->id]) }}" class="mb-3">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm" type="submit">Obriši</button>
                    </form>
                @endforeach
                <form method="POST" action="{{ route('organization.settings.catalog.categories.store', $organization->slug) }}" class="d-flex gap-2 align-items-end">
                    @csrf
                    <input type="hidden" name="kind" value="{{ $kind->value }}">
                    <div class="flex-grow-1">
                        <label class="form-label" for="noviSpor{{ $kind->value }}">Novi predmet spora</label>
                        <input name="name" id="noviSpor{{ $kind->value }}" class="form-control" required maxlength="160">
                    </div>
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Dodaj</button>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection
