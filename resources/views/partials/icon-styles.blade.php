<style>
    .ikona {
        width: var(--ikona-size, 28px);
        height: var(--ikona-size, 28px);
        border: var(--ikona-border, 0);
        background: var(--ikona-bg, transparent);
        border-radius: var(--ikona-radius, 6px);
        color: var(--ikona-fg, #5c6540);
        display: inline-grid;
        place-items: center;
        padding: 0;
        vertical-align: middle;
        box-shadow: var(--ikona-shadow, none);
        transform: rotate(var(--ikona-rotate, 0deg));
        flex-shrink: 0;
    }
    .ikona:hover {
        background: var(--ikona-bg-hover, #f3f6ea);
        color: var(--ikona-fg-hover, var(--primarna-tamna));
    }
    .ikona.opasno {
        color: var(--ikona-opasno-fg, #9b1c1c);
        background: var(--ikona-opasno-bg, transparent);
    }
    .ikona.opasno:hover {
        background: var(--ikona-opasno-hover-bg, #fdecec);
        color: var(--ikona-opasno-hover-fg, #9b1c1c);
    }
    .ikona.gotovo {
        color: var(--ikona-gotovo-fg, #2f6b3a);
        background: var(--ikona-gotovo-bg, transparent);
    }
    .ikona.gotovo:hover {
        background: var(--ikona-gotovo-hover-bg, #f3f6ea);
        color: var(--ikona-gotovo-hover-fg, #2f6b3a);
    }
    .ikona svg {
        width: var(--ikona-glyph, 15px);
        height: var(--ikona-glyph, 15px);
        display: block;
        fill: var(--ikona-fill, currentColor);
        stroke: var(--ikona-stroke, none);
        stroke-width: var(--ikona-stroke-width, 0);
        stroke-linecap: var(--ikona-linecap, round);
        stroke-linejoin: var(--ikona-linejoin, round);
        transform: rotate(var(--ikona-glyph-rotate, 0deg));
    }
    .ikona:disabled,
    .ikona:disabled:hover,
    .ikona.opasno:disabled,
    .ikona.opasno:disabled:hover,
    .ikona.gotovo:disabled,
    .ikona.gotovo:disabled:hover {
        color: var(--ikona-disabled-fg, #c5cbb0);
        background: var(--ikona-disabled-bg, transparent);
        cursor: not-allowed;
    }
@foreach(\App\Support\IconStyles::cssVariables() as $key => $vars)
    body.ikone-{{ $key }},
    .ikona-izbor-red.ikone-{{ $key }} {
@foreach($vars as $name => $value)
        {{ $name }}: {{ $value }};
@endforeach
    }
@endforeach
    .ikona-izbori { display: flex; flex-wrap: wrap; gap: 8px; }
    .ikona-izbor {
        width: 148px;
        border: 1px solid rgba(0, 0, 0, .08);
        background: #fff;
        border-radius: 8px;
        padding: 8px;
        text-align: left;
        cursor: pointer;
    }
    .ikona-izbor.aktivna { box-shadow: 0 0 0 2px var(--crta-zaglavlja, var(--primarna-zelena)); }
    .ikona-izbor-naziv { display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #1c1c1a; }
    .ikona-izbor-red { display: flex; gap: 2px; align-items: center; min-height: 34px; }
</style>
