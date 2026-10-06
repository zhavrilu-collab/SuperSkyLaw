<!DOCTYPE html>
<html lang="hr">
<head><meta charset="utf-8"><title>Račun {{ $invoice->number }}</title>
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #2b3a2b; }
h1 { color: #1b431c; font-size: 18px; }
table { width: 100%; border-collapse: collapse; }
th, td { border-bottom: 1px solid #ddd; padding: 6px; text-align: left; }
th { color: #1b431c; }
</style>
</head>
<body>
<h1>{{ $organization->name }}</h1>
<p>OIB: {{ $organization->oib }}<br>{{ $organization->address }} {{ $organization->city }}<br>{{ $organization->email }}</p>
<h2>Račun {{ $invoice->number }}</h2>
<p>Datum: {{ $invoice->issue_date->format('d.m.Y.') }} · Dospijeće: {{ $invoice->due_date->format('d.m.Y.') }}</p>
<p><strong>Kupac:</strong> {{ $invoice->buyer_name }}<br>OIB: {{ $invoice->buyer_oib }}<br>{{ $invoice->buyer_address }}</p>
<p>Predmet: {{ $invoice->matter->internal_number }} — {{ $invoice->matter->title }}</p>
<table>
<thead><tr><th>Stavka</th><th>Količina</th><th>Iznos EUR</th></tr></thead>
<tbody>
@foreach($invoice->lines as $line)
<tr><td>{{ $line->description }}</td><td>{{ $line->quantity }}</td><td>{{ number_format($line->line_total_cents/100, 2, ',', '.') }}</td></tr>
@endforeach
</tbody>
</table>
<p>Osnovica: {{ number_format($invoice->subtotal_cents/100, 2, ',', '.') }} EUR<br>
PDV {{ $invoice->vat_rate }}%: {{ number_format($invoice->vat_cents/100, 2, ',', '.') }} EUR<br>
<strong>Ukupno: {{ number_format($invoice->total_cents/100, 2, ',', '.') }} EUR</strong></p>
</body>
</html>
