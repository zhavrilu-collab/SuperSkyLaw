Podsjetnik ({{ $offsetLabel }} prije) za: {{ $event->title }}
Termin: {{ $event->starts_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}
@if($event->court_name)
Sud: {{ $event->court_name }}
@endif
@if($event->is_preclusive)
Rok je prekluzivan.
@endif
