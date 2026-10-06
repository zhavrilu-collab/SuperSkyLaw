@extends('layouts.guest')
@section('title', 'Odabir ureda — SuperSkyLaw')
@section('content')
<div class="card"><div class="card-body p-4">
    <h1 class="h5 text-tema mb-3">Odaberi ured</h1>
    <form method="POST" action="{{ route('organization.pick.store') }}">
        @csrf
        <div class="list-group mb-3">
            @foreach($entries as $entry)
                <label class="list-group-item">
                    <input type="radio" name="slug" value="{{ $entry->organization->slug }}" class="form-check-input me-2" required>
                    <strong>{{ $entry->organization->name }}</strong>
                    <span class="text-muted small">· {{ $entry->role->label() }}</span>
                </label>
            @endforeach
        </div>
        <button type="submit" class="btn btn-primary">Nastavi</button>
    </form>
</div></div>
@endsection
