@extends('layouts.app')
@section('title', 'Troškovnik')
@section('nav-suffix', 'Troškovnik')
@section('content')
<h1 class="h5 text-tema mb-3">Troškovnik</h1>
<p class="text-muted">Nagrada klijentu ide na račun. Ovdje je ono što se potražuje od protivne strane.</p>
<form method="GET" class="kartica-kontejner mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label">Predmet</label>
            <select name="matter_id" class="form-select" required>
                <option value="">Odaberi</option>
                @foreach($matters as $item)
                    <option value="{{ $item->id }}" @selected($matter && $matter->id === $item->id)>{{ $item->internal_number }} — {{ $item->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary" type="submit">Prikaži</button></div>
        @if($matter)
        <div class="col-md-3"><a class="btn btn-outline-success" href="{{ route('organization.cost-bills.pdf', [$org->slug, $matter->id]) }}">PDF</a></div>
        @endif
    </div>
</form>
@if($matter)
<div class="row g-3">
    <div class="col-lg-6">
        <div class="kartica-kontejner">
            <h2 class="h6 text-tema">Nagrada prema protivnoj strani</h2>
            @forelse($fees as $fee)
                <div class="border-bottom py-2">{{ $fee->description }} — {{ $fee->points }} bodova — {{ number_format($fee->amount_cents / 100, 2, ',', '.') }} EUR</div>
            @empty
                <p class="text-muted mb-0">Nema tarifnih stavki za protivnu stranu.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="kartica-kontejner">
            <h2 class="h6 text-tema">Troškovi</h2>
            @forelse($costs as $cost)
                <div class="border-bottom py-2">{{ $cost->category->label() }} — {{ $cost->description }} — {{ number_format($cost->amount_cents / 100, 2, ',', '.') }} EUR</div>
            @empty
                <p class="text-muted mb-0">Nema pristojbe, vještačenja, puta ni poštarine na teret protivne strane.</p>
            @endforelse
        </div>
    </div>
</div>
@endif
@endsection
