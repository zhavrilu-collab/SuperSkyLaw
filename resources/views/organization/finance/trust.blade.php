@extends('layouts.app')
@section('title', 'Depozit')
@section('nav-suffix', 'Financije')
@section('content')
<h1 class="h5 text-tema mb-3">Depozitni račun</h1>
<p class="text-muted">Sredstva stranaka vode se odvojeno od poslovnog IBAN-a{{ $trustIban ? ' '.$trustIban : '' }}. Stanje: <strong>{{ number_format($balanceCents / 100, 2, ',', '.') }} EUR</strong></p>
@perm('finance.manage')
<form method="POST" action="{{ route('organization.trust.store', $org->slug) }}" class="kartica-kontejner mb-3">
    @csrf
    <div class="row g-2">
        <div class="col-md-3"><select name="matter_id" class="form-select" required>@foreach($matters as $matter)<option value="{{ $matter->id }}">{{ $matter->internal_number }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="direction" class="form-select">@foreach(\App\Enums\TrustDirection::cases() as $direction)<option value="{{ $direction->value }}">{{ $direction->label() }}</option>@endforeach</select></div>
        <div class="col-md-2"><input name="amount" type="number" step="0.01" min="0.01" class="form-control" placeholder="Iznos EUR" required></div>
        <div class="col-md-2"><input name="counterparty" class="form-control" placeholder="Od koga / kome"></div>
        <div class="col-md-3"><input name="purpose" class="form-control" placeholder="Svrha" required></div>
        <div class="col-12"><button class="btn btn-primary btn-sm" type="submit">Upiši</button></div>
    </div>
</form>
@endperm
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
