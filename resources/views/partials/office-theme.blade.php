@php
    $officeTheme = $officeTheme ?? \App\Support\OfficeThemes::palette(null);
@endphp
<style>
    :root {
        --primarna-zelena: {{ $officeTheme['primary'] }};
        --primarna-tamna: {{ $officeTheme['dark'] }};
        --svijetlo-zelena: {{ $officeTheme['light'] }};
        --bordo-crvena: {{ $officeTheme['accent'] }};
        --zlatna-tradicija: {{ $officeTheme['gold'] }};
        --tekst-tamni: {{ $officeTheme['text'] }};
        --tekst-na-primarnoj: {{ $officeTheme['onPrimary'] }};
        --tema-rgb: {{ $officeTheme['rgb'] }};
        --tema: var(--primarna-zelena);
        --tema-svijetla: var(--svijetlo-zelena);
        --tema-greska-svijetla: #fff5f5;
        --crta-zaglavlja: {{ $officeTheme['logoMark'] ?? $officeTheme['primary'] }};
        --sustav-greska: #e31e24;
        --sustav-greska-tinta: #560b0e;
        {!! \App\Support\ThemeRecipes::chromeDeclarations($officeTheme) !!}
    }
</style>
