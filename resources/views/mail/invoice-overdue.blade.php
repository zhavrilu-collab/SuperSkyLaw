Poštovani,

račun {{ $invoice->number }} od {{ $invoice->issue_date->format('d.m.Y.') }} dospio je {{ $invoice->due_date->format('d.m.Y.') }} ({{ $daysAfterDue }} dana).

Iznos: {{ number_format($invoice->total_cents / 100, 2, ',', '.') }} EUR
Plaćeno: {{ number_format($invoice->paid_cents / 100, 2, ',', '.') }} EUR
Predmet: {{ $invoice->matter->internal_number }} — {{ $invoice->matter->title }}

Molimo podmirenje duga.

{{ $invoice->matter->organization->name }}
