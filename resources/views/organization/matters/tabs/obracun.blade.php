@php
    $eur = fn (int $cents): string => number_format($cents / 100, 2, ',', '.').' EUR';
    $nenaplaceno = $ledger['unbilledTimeCents'] + $ledger['unbilledExpenseCents'] + $ledger['unbilledTariffCents'];
@endphp
<div class="kartica-kontejner">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h2 class="h6 text-tema mb-1">Obračun predmeta</h2>
            <p class="text-muted mb-0">Sati, troškovi, tarifa, računi i depozit ovog predmeta. Uredski izvještaji ostaju u Financijama.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('organization.invoices.index', $org->slug) }}">Financije</a>
            @planFeature('tariff_hok')
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('organization.cost-bills.pdf', [$org->slug, $matter->id]) }}">Troškovnik PDF</a>
            @endplanFeature
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small fw-bold">Sati</div><div>{{ $eur($ledger['unbilledTimeCents']) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small fw-bold">Troškovi</div><div>{{ $eur($ledger['unbilledExpenseCents']) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small fw-bold">Tarifa</div><div>{{ $eur($ledger['unbilledTariffCents']) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small fw-bold">Nenaplaćeno</div><div>{{ $eur($nenaplaceno) }}</div></div></div>
    </div>
    <p class="small text-muted">Nenaplaćeno zbraja odobrene sate, troškove klijentu i nagradu klijentu koji još nisu na računu.</p>
    @if($ledger['pendingTimeCents'] > 0)
        <p class="small text-muted">Sati koji čekaju odobrenje: {{ $eur($ledger['pendingTimeCents']) }}.</p>
    @endif

    <h2 class="h6 text-tema mt-3">Sati</h2>
    @if($ledger['times']->isEmpty())
        <p class="text-muted">Nema sati.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Kad</th><th>Tko</th><th>Opis</th><th>Minute</th><th>Stanje</th><th>Iznos</th><th>Račun</th></tr></thead>
                <tbody>
                    @foreach($ledger['times'] as $entry)
                        <tr>
                            <td>{{ ($entry->started_at ?? $entry->created_at)->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</td>
                            <td>{{ $entry->user?->name ?: '—' }}</td>
                            <td>{{ $entry->description }}</td>
                            <td>{{ $entry->isRunning() ? 'U tijeku' : $entry->minutes }}</td>
                            <td>{{ $entry->status->label() }}</td>
                            <td>{{ $eur($entry->valueCents()) }}</td>
                            <td>{{ $entry->invoice_id ? 'Na računu' : 'Nije na računu' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2 class="h6 text-tema mt-3">Troškovi</h2>
    @if($ledger['expenses']->isEmpty())
        <p class="text-muted">Nema troškova.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Opis</th><th>Vrsta</th><th>Kome</th><th>Iznos</th><th>Račun</th></tr></thead>
                <tbody>
                    @foreach($ledger['expenses'] as $expense)
                        <tr>
                            <td>{{ $expense->description }}</td>
                            <td>{{ $expense->category->label() }}</td>
                            <td>{{ $expense->bill_to === \App\Enums\FeeAudience::Client->value ? 'Klijent' : 'Protivna strana' }}</td>
                            <td>{{ $eur($expense->amount_cents) }}</td>
                            <td>{{ $expense->invoice_id ? 'Na računu' : 'Nije na računu' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2 class="h6 text-tema mt-3">Tarifa</h2>
    @if($ledger['tariffs']->isEmpty())
        <p class="text-muted">Nema tarife.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Opis</th><th>Kome</th><th>Bodovi</th><th>Iznos</th><th>Račun</th></tr></thead>
                <tbody>
                    @foreach($ledger['tariffs'] as $charge)
                        <tr>
                            <td>{{ $charge->description }}</td>
                            <td>{{ $charge->audience->label() }}</td>
                            <td>{{ $charge->points }}</td>
                            <td>{{ $eur($charge->amount_cents) }}</td>
                            <td>{{ $charge->invoice_id ? 'Na računu' : 'Nije na računu' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2 class="h6 text-tema mt-3">Računi</h2>
    @if($ledger['invoices']->isEmpty())
        <p class="text-muted">Nema računa.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Broj</th><th>Datum</th><th>Ukupno</th><th>Plaćeno</th><th>Stanje</th></tr></thead>
                <tbody>
                    @foreach($ledger['invoices'] as $invoice)
                        <tr>
                            <td><a href="{{ route('organization.invoices.show', [$org->slug, $invoice->id]) }}">{{ $invoice->number }}</a></td>
                            <td>{{ $invoice->issue_date->timezone(config('app.timezone'))->format('d.m.Y.') }}</td>
                            <td>{{ $eur($invoice->total_cents) }}</td>
                            <td>{{ $eur($invoice->paid_cents) }}</td>
                            <td>{{ $invoice->status->label() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2 class="h6 text-tema mt-3">Depozit</h2>
    <p>Stanje: <strong>{{ $eur($ledger['trustBalanceCents']) }}</strong></p>
    @if($ledger['trustMovements']->isEmpty())
        <p class="text-muted mb-0">Nema uplata ni isplata.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Kad</th><th>Smjer</th><th>Svrha</th><th>Iznos</th></tr></thead>
                <tbody>
                    @foreach($ledger['trustMovements'] as $movement)
                        <tr>
                            <td>{{ $movement->occurred_on->timezone(config('app.timezone'))->format('d.m.Y.') }}</td>
                            <td>{{ $movement->direction->label() }}</td>
                            <td>{{ $movement->purpose }}@if($movement->counterparty) · {{ $movement->counterparty }}@endif</td>
                            <td>{{ $eur($movement->amount_cents) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
