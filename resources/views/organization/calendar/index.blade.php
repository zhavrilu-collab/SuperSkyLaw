@extends('layouts.app')
@section('title', 'Kalendar')
@section('nav-suffix', 'Kalendar')
@section('content')
<h1 class="h5 text-tema mb-3">Kalendar</h1>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="kartica-kontejner">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Termin</th><th>Vrsta</th><th>Predmet</th><th></th></tr></thead>
                    <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>{{ $event->starts_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}<div class="text-muted">{{ $event->title }}</div></td>
                            <td>{{ $event->type->label() }} @if($event->is_preclusive)<span class="text-danger">prekluzivno</span>@endif</td>
                            <td>{{ $event->matter?->internal_number ?: '—' }}
                                @if($event->e_oglasna_url)<div><a href="{{ $event->e_oglasna_url }}" target="_blank" rel="noopener">e-Oglasna</a></div>@endif
                            </td>
                            <td>
                                @perm('calendar.manage')
                                @if(!$event->completed_at)
                                <form method="POST" action="{{ route('organization.calendar.complete', [$org->slug, $event->id]) }}">@csrf<button class="btn btn-sm btn-outline-success" type="submit">Obavljeno</button></form>
                                @endif
                                @if($event->matter && $event->matter->parties->isNotEmpty())
                                <form method="POST" action="{{ route('organization.calendar.sms', [$org->slug, $event->id]) }}" class="mt-1">
                                    @csrf
                                    <select name="party_id" class="form-select form-select-sm mb-1">
                                        @foreach($event->matter->parties as $link)
                                            <option value="{{ $link->party_id }}">{{ $link->party->name }}{{ $link->party->sms_consent_at ? '' : ' (nema privole)' }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">SMS</button>
                                </form>
                                @endif
                                @endperm
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">Nema termina.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @perm('calendar.manage')
    <div class="col-lg-5">
        <form method="POST" action="{{ route('organization.calendar.store', $org->slug) }}" class="kartica-kontejner">
            @csrf
            <h2 class="h6 text-tema">Novi termin</h2>
            <div class="mb-2"><input name="title" class="form-control" placeholder="Naziv" required></div>
            <div class="mb-2"><select name="type" class="form-select">@foreach(\App\Enums\CourtEventType::cases() as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select></div>
            <div class="mb-2"><select name="matter_id" class="form-select"><option value="">Bez predmeta</option>@foreach($matters as $matter)<option value="{{ $matter->id }}">{{ $matter->internal_number }}</option>@endforeach</select></div>
            <div class="mb-2"><input name="court_name" class="form-control" placeholder="Sud"></div>
            <div class="mb-2"><input name="e_oglasna_url" type="url" class="form-control" placeholder="Poveznica na e-Oglasnu"></div>
            <p class="small text-muted">Automatski dohvat e-Oglasne nije spojen. Poveznicu i rok upisuje ured.</p>
            <div class="mb-2"><input type="datetime-local" name="starts_at" class="form-control" required></div>
            <div class="mb-2"><select name="responsible_user_id" class="form-select">@foreach($members as $member)<option value="{{ $member->user_id }}">{{ $member->user->name }}</option>@endforeach</select></div>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_preclusive" value="1" id="pre"><label class="form-check-label" for="pre">Prekluzivni rok</label></div>
            <button class="btn btn-primary btn-sm" type="submit">Spremi</button>
        </form>
    </div>
    @endperm
</div>
@planFeature('calendar_sync')
<div class="kartica-kontejner mt-3">
    <h2 class="h6 text-tema">Vanjski kalendar</h2>
    <p class="mb-2"><a href="{{ route('organization.calendar.export', $org->slug) }}">Preuzmi ICS</a></p>
    <p class="small text-muted">Google ili Outlook mogu se pretplatiti na: {{ route('calendar.feed', $org->feedToken('calendar_feed_token')) }}</p>
    @perm('calendar.manage')
    <form method="POST" action="{{ route('organization.calendar.import', $org->slug) }}" class="row g-2">
        @csrf
        <div class="col-md-8"><input name="feed_url" type="url" class="form-control" placeholder="Adresa vanjskog ICS feeda" required></div>
        <div class="col-md-4"><button class="btn btn-primary btn-sm" type="submit">Uvezi</button></div>
    </form>
    @endperm
</div>
@endplanFeature
@endsection
