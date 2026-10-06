@extends('layouts.guest')
@section('title', 'Čekanje odobrenja — SuperSkyLaw')
@section('content')
<div class="card"><div class="card-body p-4">
    <h1 class="h5 text-tema mb-3">Registracija na čekanju</h1>
    <p class="text-muted">Super-administrator mora odobriti ured prije pristupa sustavu.</p>
    <ul class="list-group mb-3">
        @foreach($pendingOrganizations as $organization)
            <li class="list-group-item d-flex justify-content-between"><span>{{ $organization->name }}</span><span class="badge bg-warning text-dark">{{ $organization->status->label() }}</span></li>
        @endforeach
    </ul>
    <p class="small text-muted mb-0">Stranica se automatski osvježava svakih 15 sekundi.</p>
</div></div>
@endsection
@push('scripts')
<script>
setInterval(() => {
    fetch('{{ route('registration.pending.status') }}')
        .then(r => r.json())
        .then(data => { if (data.redirect_url) window.location = data.redirect_url; });
}, 15000);
</script>
@endpush
