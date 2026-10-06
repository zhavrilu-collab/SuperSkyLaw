@extends('layouts.portal')
@section('title', $matter->internal_number)
@section('content')
<p class="mb-2"><a href="{{ route('portal.home', $org->slug) }}">Moji predmeti</a></p>
<h1 class="h5 text-tema">{{ $matter->internal_number }} — {{ $matter->title }}</h1>
<p class="text-muted">{{ $matter->courtReference() }}</p>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="kartica-kontejner">
            <h2 class="h6 text-tema">Rokovi i ročišta</h2>
            @forelse($matter->courtEvents as $event)
                <div class="border-bottom py-2">
                    <div class="fw-semibold">{{ $event->title }}</div>
                    <div class="text-muted">{{ $event->starts_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }} · {{ $event->type->label() }} @if($event->is_preclusive)· prekluzivno @endif</div>
                </div>
            @empty
                <p class="text-muted mb-0">Nema termina.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="kartica-kontejner">
            <h2 class="h6 text-tema">Obavijesti</h2>
            @forelse($matter->timelineEntries as $entry)
                <div class="border-bottom py-2">
                    <div class="fw-semibold">{{ $entry->type->label() }} · {{ $entry->occurred_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</div>
                    <div>{{ $entry->body }}</div>
                </div>
            @empty
                <p class="text-muted mb-0">Nema obavijesti.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="kartica-kontejner">
            <h2 class="h6 text-tema">Računi</h2>
            @forelse($matter->invoices as $invoice)
                <div class="border-bottom py-2 d-flex justify-content-between">
                    <span>{{ $invoice->number }} · {{ $invoice->status->label() }} · {{ number_format($invoice->total_cents / 100, 2, ',', '.') }} EUR</span>
                    <a href="{{ route('portal.invoices.pdf', [$org->slug, $invoice->id]) }}">PDF</a>
                </div>
            @empty
                <p class="text-muted mb-0">Nema računa.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="kartica-kontejner">
            <h2 class="h6 text-tema">Dokumenti</h2>
            @forelse($matter->documents as $document)
                <div class="border-bottom py-2"><a href="{{ route('portal.documents.download', [$org->slug, $document->id]) }}">{{ $document->original_name }}</a></div>
            @empty
                <p class="text-muted mb-0">Nema dijeljenih dokumenata.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
