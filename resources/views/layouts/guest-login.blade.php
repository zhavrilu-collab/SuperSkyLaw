@php
    use App\Models\Organization;
    use App\Support\OfficeThemes;

    $loginOrganization = ($organization ?? null) instanceof Organization
        ? $organization
        : (app()->bound('currentOrganization') && app('currentOrganization') instanceof Organization
            ? app('currentOrganization')
            : null);
    $loginThemeKey = OfficeThemes::resolve($loginOrganization?->theme_color);
    $loginPalette = OfficeThemes::palette($loginThemeKey);
    $loginLogo = asset(OfficeThemes::verticalPath($loginThemeKey));
    $loginFocus = 'rgba('.$loginPalette['rgb'].', 0.25)';
@endphp
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $loginPalette['primary'] }}">
    <title>@yield('title', 'Prijava — SuperSkyLaw')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primarna-zelena: {{ $loginPalette['primary'] }};
            --primarna-tamna: {{ $loginPalette['dark'] }};
            --tema-sjena-fokus: {{ $loginFocus }};
        }
        .login-tagline { min-height: 3em; }
        .btn-login {
            --bs-btn-color: #fff;
            --bs-btn-bg: var(--primarna-zelena);
            --bs-btn-border-color: var(--primarna-zelena);
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: var(--primarna-tamna);
            --bs-btn-hover-border-color: var(--primarna-tamna);
            --bs-btn-focus-shadow-rgb: {{ $loginPalette['rgb'] }};
            --bs-btn-active-color: #fff;
            --bs-btn-active-bg: var(--primarna-tamna);
            --bs-btn-active-border-color: var(--primarna-tamna);
            --bs-btn-disabled-color: #fff;
            --bs-btn-disabled-bg: var(--primarna-zelena);
            --bs-btn-disabled-border-color: var(--primarna-zelena);
        }
        .login-card a { color: var(--primarna-zelena); }
        .login-card a:hover { color: var(--primarna-tamna); }
        .form-control:focus, .form-check-input:focus {
            border-color: var(--primarna-zelena);
            box-shadow: 0 0 0 0.25rem var(--tema-sjena-fokus);
        }
        .form-check-input:checked {
            background-color: var(--primarna-zelena);
            border-color: var(--primarna-zelena);
        }
    </style>
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="text-center mb-4">
                <img src="{{ $loginLogo }}" alt="SuperSkyLaw" class="d-block mx-auto mb-3" style="max-width: 210px; width: 100%; height: auto;">
                <p class="text-muted small mb-0 login-tagline">@yield('tagline')</p>
            </div>
            <div class="card border-0 shadow-sm login-card">
                <div class="card-body p-4">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
