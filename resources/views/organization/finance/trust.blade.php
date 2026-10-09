@extends('layouts.app')
@section('title', 'Depozit')
@section('nav-suffix', 'Financije')
@section('content')
<h1 class="h5 text-tema mb-3">Depozitni račun</h1>
<p class="text-muted">Sredstva stranaka vode se odvojeno od poslovnog IBAN-a{{ $trustIban ? ' '.$trustIban : '' }}. Stanje: <strong>{{ number_format($balanceCents / 100, 2, ',', '.') }} EUR</strong></p>
<p class="text-muted">Uplata i isplata upisuju se na predmetu, u Obračunu.</p>
<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Datum</th><th>Predmet</th><th>Svrha</th><th class="text-end">Iznos</th></tr></thead>
            <tbody>
            @forelse($movements as $movement)
                <tr>
                    <td>{{ $movement->occurred_on->format('d.m.Y.') }}</td>
                    <td>{{ $movement->matter->internal_number }}</td>
                    <td>{{ $movement->direction->label() }} · {{ $movement->purpose }}</td>
                    <td class="text-end">{{ $movement->direction->value === 'out' ? '−' : '' }}{{ number_format($movement->amount_cents / 100, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">Nema prometa.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
