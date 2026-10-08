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
    @stack('styles')
</head>
<body>
@php
    $org = app()->bound('currentOrganization') ? app('currentOrganization') : null;
    $orgUser = app()->bound('currentOrganizationUser') ? app('currentOrganizationUser') : null;
    $navUser = auth()->user();
    $navEmail = $navUser?->email ?? '';
    $unreadNotifications = ($org && $navUser)
        ? \App\Models\OfficeNotification::query()->where('user_id', $navUser->id)->whereNull('read_at')->count()
        : 0;
@endphp
@if($org && $orgUser)
<div class="app-shell" id="appShell">
    <div class="app-sidebar-backdrop" id="appSidebarBackdrop" hidden></div>
    <aside class="app-sidebar" id="appSidebar">
        <a class="app-sidebar-brand" href="{{ route('organization.dashboard', $org->slug) }}">
            <span class="app-sidebar-lockup" id="appSidebarBrandLogo"
                  style="--lockup: url('{{ asset(\App\Support\OfficeThemes::horizontalPath($org->themeColor())) }}')">
                <span class="app-sidebar-mark" aria-hidden="true"></span>
                <span class="app-sidebar-word" aria-hidden="true"></span>
                <span class="visually-hidden">SuperSkyLaw</span>
            </span>
        </a>
        @include('partials.app-sidebar')
    </aside>
    <div class="app-main">
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <button type="button" class="navbar-hamburger-btn" id="navModulesMenuBtn" aria-expanded="false" aria-controls="appSidebar" title="Izbornik">
                    <span class="navbar-hamburger-icon" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span class="visually-hidden">Izbornik</span>
                </button>
                <p class="app-topbar-title">
                    <span class="app-topbar-title-prefix">{{ $org->navbarBrandPrefix() }}</span>
                </p>
            </div>
            <div class="app-topbar-user">
                <a href="{{ route('organization.notifications.index', $org->slug) }}" class="btn btn-sm btn-outline-success">Obavijesti @if($unreadNotifications > 0)({{ $unreadNotifications }})@endif</a>
                <div class="d-flex align-items-center gap-2" title="{{ $navEmail }}">
                    <div class="navbar-modules-user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($navEmail, 0, 1)) }}</div>
                    <div class="d-none d-md-block min-w-0">
                        <div class="navbar-user-bar-label">Prijavljeni korisnik</div>
                        <div class="navbar-user-bar-email text-truncate">{{ $navEmail }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mb-0">@csrf
                    <button type="submit" class="btn btn-navbar-logout">Odjava</button>
                </form>
            </div>
        </header>
        @if($org->onTrial() || $org->trialExpired())
            @include('partials.trial-notice')
        @endif
        @include('partials.flash-messages')
        <main class="pt-3 pb-5">
            <div class="container-fluid px-4">
                @yield('content')
            </div>
        </main>
    </div>
</div>
@else
    @include('partials.flash-messages')
    <main class="pt-3 pb-5">
        <div class="container-fluid px-4">@yield('content')</div>
    </main>
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    document.querySelectorAll('.app-sidebar-group-toggle').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var group = btn.closest('.app-sidebar-group');
            if (!group) return;
            var open = group.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
    var shell = document.getElementById('appShell');
    var toggle = document.getElementById('navModulesMenuBtn');
    var backdrop = document.getElementById('appSidebarBackdrop');
    function setOpen(open) {
        if (!shell || !toggle) return;
        shell.classList.toggle('sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (backdrop) backdrop.hidden = !open;
    }
    if (toggle) toggle.addEventListener('click', function () { setOpen(!shell.classList.contains('sidebar-open')); });
    if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
})();
</script>
@stack('scripts')
</body>
</html>
