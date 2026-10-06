@extends('layouts.app')
@section('title', 'Izvještaj')
@section('nav-suffix', 'Izvještaj')
@section('content')
<h1 class="h5 text-tema mb-3">Izvještaj naplate</h1>
<form method="GET" class="mb-3">
    <input type="month" name="month" value="{{ $report['month'] }}" class="form-control d-inline-block w-auto">
    <button class="btn btn-primary btn-sm" type="submit">Prikaži</button>
</form>
<div class="row g-3">
    <div class="col-md-3"><div class="kartica-kontejner"><div class="text-muted">Utilizacija</div><strong>{{ number_format($report['utilization'] * 100, 1, ',', '.') }} %</strong><div class="small text-muted">{{ $report['billable_minutes'] }} / {{ $report['available_minutes'] }} min</div></div></div>
    <div class="col-md-3"><div class="kartica-kontejner"><div class="text-muted">Realizacija</div><strong>{{ number_format($report['realization'] * 100, 1, ',', '.') }} %</strong><div class="small text-muted">fakturirano / odrađeno</div></div></div>
    <div class="col-md-3"><div class="kartica-kontejner"><div class="text-muted">Naplata</div><strong>{{ number_format($report['collection'] * 100, 1, ',', '.') }} %</strong><div class="small text-muted">{{ number_format($report['collected_cents'] / 100, 2, ',', '.') }} / {{ number_format($report['issued_cents'] / 100, 2, ',', '.') }} EUR</div></div></div>
    <div class="col-md-3"><div class="kartica-kontejner"><div class="text-muted">Odrađeno</div><strong>{{ number_format($report['worked_cents'] / 100, 2, ',', '.') }} EUR</strong></div></div>
</div>
<div class="kartica-kontejner mt-3">
    <h2 class="h6 text-tema">Starost potraživanja</h2>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Nedospjelo</th><th>1–30</th><th>31–60</th><th>61–90</th><th>90+</th></tr></thead>
            <tbody>
            <tr>
                @foreach(['current', 'd30', 'd60', 'd90', 'd90plus'] as $bucket)
                    <td>{{ number_format($report['aging'][$bucket] / 100, 2, ',', '.') }}</td>
                @endforeach
            </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
