<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portal')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.siletici-styles')
</head>
<body>
<div class="guest-shell">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a class="app-brand-lockup mb-0" href="{{ route('portal.home', $org->slug) }}">
                <img src="{{ asset('brand/superskylaw-mark.png') }}" class="app-brand-mark" alt="">
                SuperSky<span>Law</span>
            </a>
            <form method="POST" action="{{ route('portal.logout', $org->slug) }}">@csrf<button class="btn btn-navbar-logout" type="submit">Odjava</button></form>
        </div>
        @include('partials.flash-messages')
        @yield('content')
    </div>
</div>
</body>
</html>
