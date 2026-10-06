<div class="trial-notice @if($org->trialExpired()) trial-notice--expired @endif">
    <div class="trial-notice__inner">
        <span class="trial-notice__label">Probni period</span>
        <span class="trial-notice__text">
            @if($org->trialExpired())
                Probni period je istekao {{ $org->trial_ends_at->format('d.m.Y.') }}.
            @else
                Probni period traje do <strong>{{ $org->trial_ends_at->format('d.m.Y.') }}</strong>.
            @endif
        </span>
    </div>
</div>
