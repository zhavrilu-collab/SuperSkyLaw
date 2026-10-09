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
<p class="text-muted mb-0">Trošak, račun, tarifa i depozit upisuju se na predmetu, u Obračunu.</p>
@endsection
