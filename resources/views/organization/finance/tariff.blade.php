@extends('layouts.app')
@section('title', 'Tarifa HOK')
@section('nav-suffix', 'Tarifa HOK')
@section('content')
<h1 class="h5 text-tema mb-1">{{ $version->name }}</h1>
<p class="text-muted">{{ $version->citation }}. Bod vrijedi {{ number_format($version->point_value_cents / 100, 2, ',', '.') }} EUR. Obračun je pomoć uredu pri sastavljanju nagrade i troškovnika.</p>
<div class="kartica-kontejner mb-3">
    <h2 class="h6 text-tema">Rasponi po vrijednosti spora (Tbr. 7. t. 1.)</h2>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Od EUR</th><th>Do EUR</th><th>Bodovi</th></tr></thead>
            <tbody>
            @foreach($bands as $band)
                <tr>
                    <td>{{ number_format($band->value_from_cents / 100, 2, ',', '.') }}</td>
                    <td>{{ $band->value_to_cents === null ? 'i više' : number_format($band->value_to_cents / 100, 2, ',', '.') }}</td>
                    <td>
                        {{ $band->base_points }}
                        @if($band->step_cents)
                            + {{ $band->step_points }} bod na započetih {{ number_format($band->step_cents / 100, 0, ',', '.') }} EUR
                            @if($band->max_points) (najviše {{ $band->max_points }}) @endif
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@perm('finance.manage')
<form method="POST" action="{{ route('organization.tariff.store', $org->slug) }}" class="kartica-kontejner mb-3">
    @csrf
    <h2 class="h6 text-tema">Obračunaj radnju</h2>
    <div class="row g-2">
        <div class="col-md-4"><select name="matter_id" class="form-select" required>@foreach($matters as $matter)<option value="{{ $matter->id }}">{{ $matter->internal_number }} — {{ $matter->title }}</option>@endforeach</select></div>
        <div class="col-md-4"><select name="tariff_action_id" class="form-select" required>@foreach($actions as $action)<option value="{{ $action->id }}">{{ $action->label }}</option>@endforeach</select></div>
        <div class="col-md-3"><select name="audience" class="form-select">@foreach(\App\Enums\FeeAudience::cases() as $audience)<option value="{{ $audience->value }}">{{ $audience->label() }}</option>@endforeach</select></div>
        <div class="col-md-1"><button class="btn btn-primary" type="submit">Obračunaj</button></div>
    </div>
</form>
@endperm
<div class="kartica-kontejner">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Predmet</th><th>Radnja</th><th>Namjena</th><th>Bodovi</th><th>EUR</th></tr></thead>
            <tbody>
            @forelse($charges as $charge)
                <tr>
                    <td>{{ $charge->matter->internal_number }}</td>
                    <td>{{ $charge->description }}</td>
                    <td>{{ $charge->audience->label() }}</td>
                    <td>{{ $charge->points }}</td>
                    <td>{{ number_format($charge->amount_cents / 100, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">Nema obračunatih radnji.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
