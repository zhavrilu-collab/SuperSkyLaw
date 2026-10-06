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
        {!! \App\Support\ThemeRecipes::chromeDeclarations($officeTheme) !!}
    }
</style>
