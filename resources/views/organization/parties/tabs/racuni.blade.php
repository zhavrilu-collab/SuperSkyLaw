<div class="kartica-kontejner">
    @if(! $canFinance)
        <p class="text-muted mb-0">Računi su dostupni ulozi s pristupom financijama.</p>
    @elseif($invoices->isEmpty())
        <p class="text-muted mb-0">Nema računa za ovu stranku.</p>
    @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Račun</th><th>Datum</th><th>Predmet</th><th>Ukupno</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('organization.invoices.show', [$org->slug, $invoice->id]) }}">{{ $invoice->number }}</a></td>
                        <td>{{ $invoice->issue_date?->format('d.m.Y.') }}</td>
                        <td>@if($invoice->matter)<a href="{{ route('organization.matters.show', [$org->slug, $invoice->matter->id]) }}">{{ $invoice->matter->internal_number }}</a>@else — @endif</td>
                        <td>{{ number_format($invoice->total_cents / 100, 2, ',', '.') }}</td>
                        <td>{{ $invoice->status->label() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
