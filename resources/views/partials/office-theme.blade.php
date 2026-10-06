@php
    $officeTheme = $officeTheme ?? \App\Support\OfficeThemes::palette(null);
@endphp
<style>
    :root {
        --primarna-zelena: {{ $officeTheme['primary'] }};
        --primarna-tamna: {{ $officeTheme['dark'] }};
        --svijetlo-zelena: {{ $officeTheme['light'] }};
        --zlatna-tradicija: {{ $officeTheme['accent'] }};
        --tekst-tamni: {{ $officeTheme['text'] }};
        --tema-rgb: {{ $officeTheme['rgb'] }};
        --tema: var(--primarna-zelena);
        --tema-svijetla: var(--svijetlo-zelena);
    }
</style>
