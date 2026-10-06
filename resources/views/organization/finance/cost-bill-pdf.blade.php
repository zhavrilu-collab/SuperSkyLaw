<!DOCTYPE html>
<html lang="hr">
<head><meta charset="utf-8"><title>Troškovnik {{ $matter->internal_number }}</title>
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #2b3a2b; }
h1 { color: #1b431c; font-size: 18px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
th, td { border-bottom: 1px solid #ddd; padding: 6px; text-align: left; }
th { color: #1b431c; }
</style>
</head>
<body>
<h1>{{ $organization->name }}</h1>
<p>Troškovnik — {{ $matter->internal_number }} {{ $matter->title }}<br>{{ $matter->courtReference() }}</p>
<h2>Nagrada</h2>
<table>
<thead><tr><th>Radnja</th><th>Bodovi</th><th>EUR</th></tr></thead>
<tbody>
@forelse($fees as $fee)
<tr><td>{{ $fee->description }}</td><td>{{ $fee->points }}</td><td>{{ number_format($fee->amount_cents / 100, 2, ',', '.') }}</td></tr>
@empty
<tr><td colspan="3">Nema nagrade prema protivnoj strani.</td></tr>
@endforelse
</tbody>
</table>
<p>Nagrada ukupno: {{ number_format($fees->sum('amount_cents') / 100, 2, ',', '.') }} EUR</p>
<h2>Troškovi</h2>
<table>
<thead><tr><th>Vrsta</th><th>Opis</th><th>EUR</th></tr></thead>
<tbody>
@forelse($costs as $cost)
<tr><td>{{ $cost->category->label() }}</td><td>{{ $cost->description }}</td><td>{{ number_format($cost->amount_cents / 100, 2, ',', '.') }}</td></tr>
@empty
<tr><td colspan="3">Nema troškova.</td></tr>
@endforelse
</tbody>
</table>
<p>Troškovi ukupno: {{ number_format($costs->sum('amount_cents') / 100, 2, ',', '.') }} EUR</p>
<p><strong>Ukupno prema protivnoj strani: {{ number_format(($fees->sum('amount_cents') + $costs->sum('amount_cents')) / 100, 2, ',', '.') }} EUR</strong></p>
<p>Nagrada klijentu fakturira se zasebnim računom i nije dio ovog troškovnika.</p>
</body>
</html>
