@extends('layouts.app')
@section('title', $matter->internal_number)
@section('nav-suffix', 'Predmeti')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <div>
        <h1 class="h5 text-tema mb-0">{{ $matter->internal_number }} — {{ $matter->title }}</h1>
        <div class="text-muted">{{ $matter->kind->label() }} · {{ $matter->status->label() }} · {{ $matter->courtReference() }}</div>
    </div>
    @perm('matters.delete')
    <form method="POST" action="{{ route('organization.matters.destroy', [$org->slug, $matter->id]) }}">@csrf @method('DELETE')
        <button class="btn btn-outline-danger btn-sm" type="submit">Arhiviraj</button>
    </form>
    @endperm
</div>
<div class="row g-3">
    <div class="col-lg-7">
        @perm('matters.manage')
        <form method="POST" action="{{ route('organization.matters.update', [$org->slug, $matter->id]) }}" class="kartica-kontejner mb-3">
            @csrf @method('PUT')
            @include('organization.matters.fields')
            <button class="btn btn-primary btn-sm" type="submit">Spremi</button>
        </form>
        @endperm
        <div class="kartica-kontejner">
            <h2 class="h6 text-tema">Kronologija</h2>
            @foreach($matter->timelineEntries->sortByDesc('occurred_at') as $entry)
                <div class="border-bottom py-2">
                    <div class="fw-semibold">{{ $entry->type->label() }} · {{ $entry->occurred_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }} @if($entry->visible_to_client)<span class="text-tema">· vidi klijent</span>@endif</div>
                    <div>{{ $entry->body }}</div>
                </div>
            @endforeach
            @perm('matters.manage')
            <form method="POST" action="{{ route('organization.matters.timeline.store', [$org->slug, $matter->id]) }}" class="mt-3">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4"><select name="type" class="form-select">@foreach(\App\Enums\TimelineEntryType::cases() as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select></div>
                    <div class="col-md-4"><input type="datetime-local" name="occurred_at" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}"></div>
                    <div class="col-12"><textarea name="body" class="form-control" required placeholder="Sadržaj"></textarea></div>
                    <div class="col-12"><label class="form-check-label"><input class="form-check-input" type="checkbox" name="visible_to_client" value="1"> Vidljivo klijentu na portalu</label></div>
                    <div class="col-12"><button class="btn btn-primary btn-sm" type="submit">Dodaj zapis</button></div>
                </div>
            </form>
            @endperm
        </div>
    </div>
    <div class="col-lg-5">
        <div class="kartica-kontejner mb-3">
            <h2 class="h6 text-tema">Stranke na predmetu</h2>
            @foreach($matter->parties as $link)
                <div class="py-1">{{ $link->party->name }} · {{ $link->role->label() }} · {{ $link->side->label() }}</div>
            @endforeach
            @if($matter->conflictChecks->isNotEmpty())
                <hr>
                <div class="small">Zadnja provjera sukoba: <strong>{{ $matter->conflictChecks->last()->result->label() }}</strong>
                    @if($matter->conflictChecks->last()->checker) ({{ $matter->conflictChecks->last()->checker->name }}) @endif
                </div>
            @endif
            @perm('matters.manage')
            <form method="POST" action="{{ route('organization.matters.parties.store', [$org->slug, $matter->id]) }}" class="mt-3">
                @csrf
                <select name="party_id" class="form-select mb-2" required>
                    @foreach($parties as $party)<option value="{{ $party->id }}">{{ $party->name }}</option>@endforeach
                </select>
                <select name="role" class="form-select mb-2">@foreach(\App\Enums\MatterPartyRole::cases() as $role)<option value="{{ $role->value }}">{{ $role->label() }}</option>@endforeach</select>
                <select name="side" class="form-select mb-2">@foreach(\App\Enums\PartySide::cases() as $side)<option value="{{ $side->value }}">{{ $side->label() }}</option>@endforeach</select>
                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="acknowledge_conflict" value="1" id="ack2"><label class="form-check-label" for="ack2">Potvrđujem provjeru sukoba</label></div>
                <button class="btn btn-primary btn-sm" type="submit">Poveži stranku</button>
            </form>
            @endperm
        </div>
        <div class="kartica-kontejner mb-3">
            <h2 class="h6 text-tema">SPNFT</h2>
            <p class="small text-muted">Checklist za predmete u kojima ured ima obvezu sprječavanja pranja novca. Nije vanjski dohvat osobnih podataka.</p>
            @perm('matters.manage')
            <form method="POST" action="{{ route('organization.matters.spnft.required', [$org->slug, $matter->id]) }}" class="mb-2">
                @csrf
                <input type="hidden" name="spnft_required" value="0">
                <label class="form-check-label"><input class="form-check-input" type="checkbox" name="spnft_required" value="1" @checked($matter->spnft_required) onchange="this.form.submit()"> Obveza vrijedi za ovaj predmet</label>
            </form>
            @endperm
            @if($matter->spnft_required)
                @foreach(config('spnft.items') as $key => $label)
                    @php $done = $matter->spnftChecks->firstWhere('item', $key); @endphp
                    <form method="POST" action="{{ route('organization.matters.spnft.toggle', [$org->slug, $matter->id]) }}" class="mb-1">
                        @csrf
                        <input type="hidden" name="item" value="{{ $key }}">
                        <button class="btn btn-sm {{ $done ? 'btn-success' : 'btn-outline-secondary' }}" type="submit" @disabled(!auth()->user() || !app(\App\Services\OrganizationRbacService::class)->can($org->id, auth()->id(), 'matters.manage'))>{{ $done ? 'Potvrđeno' : 'Potvrdi' }}</button>
                        <span class="ms-1">{{ $label }}</span>
                    </form>
                @endforeach
            @endif
        </div>
        <div class="kartica-kontejner mb-3">
            <h2 class="h6 text-tema">Zastara</h2>
            <p class="small text-muted">{{ config('limitation.disclaimer') }}</p>
            @if($matter->limitationEstimate)
                <div class="mb-2">Orijentacijski rok: <strong>{{ $matter->limitationEstimate->suggested_on->format('d.m.Y.') }}</strong> · {{ $matter->limitationEstimate->basisLabel() }}</div>
            @endif
            @perm('matters.manage')
            <form method="POST" action="{{ route('organization.matters.limitation.store', [$org->slug, $matter->id]) }}">
                @csrf
                <select name="basis" class="form-select mb-2">
                    @foreach(config('limitation.bases') as $key => $basis)
                        <option value="{{ $key }}">{{ $basis['label'] }}</option>
                    @endforeach
                </select>
                <input type="date" name="starts_on" class="form-control mb-2" required>
                <button class="btn btn-primary btn-sm" type="submit">Izračunaj i upiši rok</button>
            </form>
            @endperm
        </div>
        @perm('walls.manage')
        <div class="kartica-kontejner">
            <h2 class="h6 text-tema">Etički zid</h2>
            <p class="small text-muted">Osoba iza zida ne vidi predmet, iako je u istom uredu.</p>
            @foreach($matter->ethicalWalls as $wall)
                <div class="d-flex justify-content-between py-1">
                    <span>{{ $wall->user->name }} · {{ $wall->reason }}</span>
                    <form method="POST" action="{{ route('organization.matters.walls.destroy', [$org->slug, $matter->id, $wall->id]) }}">@csrf @method('DELETE')<button class="btn btn-link btn-sm" type="submit">Ukloni</button></form>
                </div>
            @endforeach
            <form method="POST" action="{{ route('organization.matters.walls.store', [$org->slug, $matter->id]) }}" class="mt-2">
                @csrf
                <select name="user_id" class="form-select mb-2">
                    @foreach($members as $member)
                        @if($member->user_id !== auth()->id())
                            <option value="{{ $member->user_id }}">{{ $member->user->name }}</option>
                        @endif
                    @endforeach
                </select>
                <input name="reason" class="form-control mb-2" placeholder="Razlog" required>
                <button class="btn btn-outline-danger btn-sm" type="submit">Zatvori pristup</button>
            </form>
        </div>
        @endperm
    </div>
</div>
@endsection
