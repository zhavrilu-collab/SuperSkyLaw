@extends('layouts.app')
@section('title', 'Financije')
@section('nav-suffix', 'Financije')
@section('content')
<h1 class="h5 text-tema mb-3">Financije</h1>
<p class="mb-3">
    <a href="{{ route('organization.invoices.index', $org->slug) }}">Računi</a>
    @planFeature('tariff_hok')
    · <a href="{{ route('organization.tariff.index', $org->slug) }}">Tarifa HOK</a>
    · <a href="{{ route('organization.cost-bills.index', $org->slug) }}">Troškovnik</a>
    @endplanFeature
    · <a href="{{ route('organization.reports.index', $org->slug) }}">Izvještaj</a>
</p>
<div class="kartica-kontejner mb-3">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Račun</th><th>Predmet</th><th>Kupac</th><th>Ukupno</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td><a href="{{ route('organization.invoices.show', [$org->slug, $invoice->id]) }}">{{ $invoice->number }}</a></td>
                    <td>{{ $invoice->matter->internal_number }}</td>
                    <td>{{ $invoice->buyer_name }}</td>
                    <td>{{ number_format($invoice->total_cents/100, 2, ',', '.') }}</td>
                    <td>{{ $invoice->status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">Nema računa.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@perm('finance.manage')
<div class="row g-3">
    <div class="col-lg-6">
        <form method="POST" action="{{ route('organization.invoices.store', $org->slug) }}" class="kartica-kontejner">
            @csrf
            <h2 class="h6 text-tema">Novi račun</h2>
            <select name="matter_id" class="form-select mb-2" required>@foreach($matters as $matter)<option value="{{ $matter->id }}">{{ $matter->internal_number }}</option>@endforeach</select>
            <select name="party_id" class="form-select mb-2" required>@foreach($parties as $party)<option value="{{ $party->id }}">{{ $party->name }}</option>@endforeach</select>
            <div class="mb-2 small">Odobreni sati</div>
            @foreach($approvedEntries as $entry)
                <div class="form-check"><input class="form-check-input" type="checkbox" name="time_entry_ids[]" value="{{ $entry->id }}" id="t{{ $entry->id }}"><label class="form-check-label" for="t{{ $entry->id }}">{{ $entry->description }} ({{ $entry->minutes }} min)</label></div>
            @endforeach
            <div class="mb-2 small mt-2">Nagrada klijentu</div>
            @foreach($tariffCharges as $charge)
                <div class="form-check"><input class="form-check-input" type="checkbox" name="tariff_charge_ids[]" value="{{ $charge->id }}" id="c{{ $charge->id }}"><label class="form-check-label" for="c{{ $charge->id }}">{{ $charge->description }} ({{ number_format($charge->amount_cents/100, 2, ',', '.') }} EUR)</label></div>
            @endforeach
            <div class="mb-2 small mt-2">Troškovi na teret klijenta</div>
            @foreach($openExpenses as $expense)
                <div class="form-check"><input class="form-check-input" type="checkbox" name="expense_ids[]" value="{{ $expense->id }}" id="e{{ $expense->id }}"><label class="form-check-label" for="e{{ $expense->id }}">{{ $expense->description }}</label></div>
            @endforeach
            <button class="btn btn-primary btn-sm mt-2" type="submit">Izdaj račun</button>
        </form>
    </div>
    <div class="col-lg-6">
        <form method="POST" action="{{ route('organization.expenses.store', $org->slug) }}" class="kartica-kontejner">
            @csrf
            <h2 class="h6 text-tema">Trošak</h2>
            <select name="matter_id" class="form-select mb-2" required>@foreach($matters as $matter)<option value="{{ $matter->id }}">{{ $matter->internal_number }}</option>@endforeach</select>
            <select name="category" class="form-select mb-2">@foreach(\App\Enums\ExpenseCategory::cases() as $category)<option value="{{ $category->value }}">{{ $category->label() }}</option>@endforeach</select>
            <input name="description" class="form-control mb-2" placeholder="Opis" required>
            <input name="amount" class="form-control mb-2" placeholder="Iznos EUR" required>
            <select name="bill_to" class="form-select mb-2">
                <option value="client">Na teret klijenta (račun)</option>
                <option value="opposing">Na teret protivne strane (troškovnik)</option>
            </select>
            <button class="btn btn-primary btn-sm" type="submit">Unesi trošak</button>
        </form>
    </div>
</div>
@endperm
@endsection
