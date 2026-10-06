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
        .login-page { padding-top: 4.5rem; }
        .login-logo { display: block; width: 210px; height: 156px; object-fit: contain; object-position: center bottom; margin: 0 auto 1rem; }
        .login-tagline { height: 3em; line-height: 1.5; overflow: hidden; }
        .login-links { margin-top: 1rem; }
        .login-links p { height: 1.5em; line-height: 1.5; margin: 0; }
        .btn-login {
            --bs-btn-color: #fff;
            --bs-btn-bg: #212529;
            --bs-btn-border-color: #212529;
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: #424649;
            --bs-btn-hover-border-color: #373b3e;
            --bs-btn-active-color: #fff;
            --bs-btn-active-bg: #4d5154;
            --bs-btn-active-border-color: #373b3e;
            --bs-btn-disabled-color: #fff;
            --bs-btn-disabled-bg: #212529;
            --bs-btn-disabled-border-color: #212529;
        }
        .login-card a { color: #0d6efd; }
        .login-card a:hover { color: #0a58ca; }
        .form-control:focus, .form-check-input:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
</head>
<body class="bg-light">
<div class="container login-page">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="text-center mb-4">
                <img src="{{ $loginLogo }}" alt="SuperSkyLaw" class="login-logo">
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
