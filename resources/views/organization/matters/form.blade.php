@extends('layouts.app')
@section('title', 'Novi predmet')
@section('nav-suffix', 'Predmeti')
@section('content')
<h1 class="h5 text-tema mb-3">Novi predmet</h1>
@if(!empty($conflict) && ($conflict['result']->value ?? null) !== 'clear')
    <div class="alert alert-warning">
        <strong>Provjera sukoba: {{ $conflict['result']->label() }}.</strong>
        <ul class="mb-0">
            @foreach($conflict['matches'] as $match)
                <li>{{ $match['name'] }} — {{ $match['reason'] }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form method="POST" action="{{ route('organization.matters.store', $org->slug) }}" class="kartica-kontejner">
    @csrf
    @include('organization.matters.fields')
    <h2 class="h6 mt-3">Klijent</h2>
    <div class="row">
        <div class="col-md-4 mb-3"><label class="form-label">Naziv</label><input name="client_name" class="form-control" value="{{ old('client_name') }}" required></div>
        <div class="col-md-4 mb-3"><label class="form-label">OIB</label><input name="client_oib" class="form-control" value="{{ old('client_oib') }}"></div>
        <div class="col-md-4 mb-3"><label class="form-label">Vrsta</label>
            <select name="client_kind" class="form-select">
                <option value="person">Fizička osoba</option>
                <option value="company" @selected(old('client_kind') === 'company')>Pravna osoba</option>
            </select>
        </div>
    </div>
    <h2 class="h6">Protivna strana</h2>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Naziv</label><input name="opposing_name" class="form-control" value="{{ old('opposing_name') }}"></div>
        <div class="col-md-6 mb-3"><label class="form-label">OIB</label><input name="opposing_oib" class="form-control" value="{{ old('opposing_oib') }}"></div>
    </div>
    @if(!empty($conflict) && ($conflict['result']->value ?? null) !== 'clear')
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="acknowledge_conflict" value="1" id="ack">
            <label class="form-check-label" for="ack">Partner potvrđuje da je sukob razmotren</label>
        </div>
        <div class="mb-3"><label class="form-label">Bilješka</label><textarea name="conflict_note" class="form-control">{{ old('conflict_note') }}</textarea></div>
    @endif
    <button class="btn btn-primary" type="submit">Otvori predmet</button>
</form>
@endsection
