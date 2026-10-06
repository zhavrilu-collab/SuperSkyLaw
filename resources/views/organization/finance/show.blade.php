@extends('layouts.app')
@section('title', 'Račun '.$invoice->number)
@section('nav-suffix', 'Financije')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="h5 text-tema mb-0">Račun {{ $invoice->number }}</h1>
    <a class="btn btn-outline-success btn-sm" href="{{ route('organization.invoices.pdf', [$org->slug, $invoice->id]) }}">PDF</a>
    @planFeature('e_invoice')
    @perm('finance.manage')
    <form method="POST" action="{{ route('organization.invoices.einvoice', [$org->slug, $invoice->id]) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">e-Račun</button></form>
    @endperm
    @endplanFeature
</div>
@if($invoice->e_invoice_status->value !== 'none')
<p class="text-muted">e-Račun: {{ $invoice->e_invoice_status->label() }} @if($invoice->e_invoice_error) — {{ $invoice->e_invoice_error }} @endif</p>
@endif
<div class="kartica-kontejner mb-3">
    <p>Kupac: {{ $invoice->buyer_name }} @if($invoice->buyer_oib) · OIB {{ $invoice->buyer_oib }} @endif</p>
    <p>Status: <strong>{{ $invoice->status->label() }}</strong> · Ukupno {{ number_format($invoice->total_cents/100, 2, ',', '.') }} EUR (PDV {{ $invoice->vat_rate }}%)</p>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Stavka</th><th>Kol</th><th>Iznos</th></tr></thead>
            <tbody>
            @foreach($invoice->lines as $line)
                <tr><td>{{ $line->description }}</td><td>{{ $line->quantity }}</td><td>{{ number_format($line->line_total_cents/100, 2, ',', '.') }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@perm('finance.manage')
<form method="POST" action="{{ route('organization.invoices.pay', [$org->slug, $invoice->id]) }}" class="kartica-kontejner">
    @csrf
    <h2 class="h6 text-tema">Uplata</h2>
    <div class="row">
        <div class="col-md-4 mb-2"><input name="amount" class="form-control" placeholder="Iznos EUR" required></div>
        <div class="col-md-4 mb-2"><input type="date" name="paid_on" class="form-control" value="{{ now()->toDateString() }}" required></div>
        <div class="col-md-4 mb-2"><input name="note" class="form-control" placeholder="Napomena"></div>
    </div>
    <button class="btn btn-primary btn-sm" type="submit">Evidentiraj uplatu</button>
</form>
@endperm
@endsection
