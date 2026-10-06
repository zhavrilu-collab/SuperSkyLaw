@php
    $flashOk = session('status') ?: session('success');
    $flashErr = session('error');
@endphp
@if($flashOk || $flashErr || $errors->any())
    <div class="flash-toast-kontejner" aria-live="polite" id="appFlashToastHost">
        @if($flashOk)
            <div class="alert alert-success alert-tema flash-toast" data-flash-toast role="alert">
                <span class="alert-tema-ikona" aria-hidden="true">✓</span>
                <span>{{ $flashOk }}</span>
            </div>
        @endif
        @if($flashErr)
            <div class="alert alert-danger alert-tema flash-toast" data-flash-toast role="alert">
                <span class="alert-tema-ikona" aria-hidden="true">✕</span>
                <span>{{ $flashErr }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-tema flash-toast" data-flash-toast role="alert">
                <span class="alert-tema-ikona" aria-hidden="true">✕</span>
                <div>
                    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            </div>
        @endif
    </div>
@else
    <div class="flash-toast-kontejner" aria-live="polite" id="appFlashToastHost"></div>
@endif
