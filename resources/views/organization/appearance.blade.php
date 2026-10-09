@extends('layouts.app')
@section('title', 'Postavke')
@section('nav-suffix', 'Postavke')
@section('content')
<h1 class="h5 text-tema mb-3">Postavke</h1>
@php($activeTheme = \App\Support\OfficeThemes::resolve(old('theme_color', $organization->theme_color)))
@php($activeStyle = \App\Support\ThemeRecipes::effectiveStyle(old('theme_style', $organization->theme_style)))
@php($catalog = \App\Support\OfficeThemes::all())
@php($themeStyles = \App\Support\ThemeRecipes::styles())
@php($themeCards = \App\Support\OfficeThemes::combinationPayload())
<form method="POST" action="{{ route('organization.settings.theme', $organization->slug) }}" id="formTemaUreda" class="kartica-kontejner">
    @csrf
    @method('PUT')
    <span class="fw-bold text-muted small d-block mb-2">BOJA TEME</span>
    <input type="hidden" name="theme_color" id="themeColorInput" value="{{ $activeTheme }}">
    <input type="hidden" name="theme_style" id="themeStyleInput" value="{{ $activeStyle }}">
    <img id="temaLogoPregled"
         src="{{ asset(\App\Support\OfficeThemes::horizontalPath($activeTheme)) }}"
         alt="SuperSkyLaw"
         class="tema-logo-pregled">
    <div class="mb-2">
        <label class="form-label small fw-bold mb-2">Službena boja</label>
        <div class="d-flex gap-2 mb-2 flex-wrap align-items-center" id="temaBojaIzbor">
            @foreach($catalog as $key => $theme)
                <button type="button"
                        class="tema-svatch @if($key === $activeTheme) aktivna @endif"
                        data-tema="{{ $key }}"
                        style="background:{{ $theme['primary'] }};"
                        title="{{ $theme['label'] }}"
                        aria-label="{{ $theme['label'] }}"></button>
            @endforeach
        </div>
        <div class="form-text">Boja bira logotip i obitelj nijansi. Klik odmah mijenja zaslon.</div>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-bold mb-2">Tema</label>
        <div class="tema-smjerovi" id="temaSmjerIzbor">
            @foreach($themeStyles as $styleKey => $style)
                @php($card = $themeCards[$activeTheme.'|'.$styleKey])
                <button type="button"
                        class="tema-kartica @if($styleKey === $activeStyle) aktivna @endif"
                        data-stil="{{ $styleKey }}"
                        title="{{ $style['note'] }}"
                        aria-label="{{ $style['label'] }}">
                    <span class="tema-kartica-naziv">{{ $style['label'] }}</span>
                    <span class="tema-kartica-okvir">
                        <span class="tema-kartica-strana" style="background:{{ $card['sideBg'] ?? '#ffffff' }};color:{{ $card['idle'] ?? '#2a2a28' }}">
                            <span class="tema-kartica-kugla" style="background:{{ $card['logoMark'] }}"></span>
                            <span class="tema-kartica-red">Početna</span>
                            <span class="tema-kartica-red">Udruga</span>
                            <span class="tema-kartica-red">Članovi</span>
                            <span class="tema-kartica-red"><b>Komunikacija</b></span>
                            <span class="tema-kartica-stavka" style="background:{{ $card['navBg'] }};color:{{ $card['navFg'] }};border-left-color:{{ $card['navBar'] }};font-weight:{{ $card['navWeight'] ?? '650' }}">Poruke</span>
                            <span class="tema-kartica-red tema-kartica-pod">Predlošci</span>
                        </span>
                        <span class="tema-kartica-sadrzaj" style="background:{{ $card['light'] }};color:{{ $card['text'] }}">
                            <span class="tema-kartica-gumbi">
                                <span class="tema-kartica-gumb" style="background:{{ $card['btnBg'] }};color:{{ $card['btnFg'] }};border-color:{{ $card['btnBorder'] }}">Poziv</span>
                                <span class="tema-kartica-gumb tema-kartica-obrub" style="background:#fff;color:{{ $card['primary'] }};border-color:{{ $card['primary'] }}">Obavijest</span>
                            </span>
                            <span class="tema-kartica-lista">Ivan Pudak<br>Daria Macan</span>
                        </span>
                    </span>
                </button>
            @endforeach
        </div>
        <div class="form-text">Tema bira kako se nijanse slažu. Klik odmah pokazuje cijeli zaslon.</div>
    </div>
    @error('theme_color')
        <div class="text-danger small mb-2">{{ $message }}</div>
    @enderror
    @error('theme_style')
        <div class="text-danger small mb-2">{{ $message }}</div>
    @enderror
    <button type="submit" class="btn btn-success btn-sm btn-spremi">Spremi temu</button>
    <span id="temaPoruka" class="ms-2 small text-muted"></span>
</form>
@endsection

@push('scripts')
<script>
window.THEME_PREVIEW = @json(\App\Support\OfficeThemes::clientPreview($organization->theme_color, $organization->theme_style));
</script>
<script src="{{ asset('js/theme-preview.js') }}?v={{ filemtime(public_path('js/theme-preview.js')) }}"></script>
@endpush
