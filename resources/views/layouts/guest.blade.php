<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $officeTheme['primary'] }}">
    <title>@yield('title', 'SuperSkyLaw')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.siletici-styles')
    @include('partials.office-theme')
</head>
<body>
<div class="guest-shell">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="@yield('guest-width', 'col-md-5')">
                <div class="app-brand-lockup">
                    <img src="{{ asset(\App\Support\OfficeThemes::verticalPath($officeThemeKey)) }}" class="app-brand-logo" alt="SuperSkyLaw">
                </div>
                @include('partials.flash-messages')
                @yield('content')
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
@include('partials.obrazac-provjera')
</body>
</html>
