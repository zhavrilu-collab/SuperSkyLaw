<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SMB SaaS')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
@php($organization = app('currentOrganization'))
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand">{{ $organization->name }}</span>
        <div class="d-flex gap-2">
            @if(auth()->user() && app(\App\Services\OrganizationRbacService::class)->can($organization->id, auth()->id(), 'team.manage'))
                <a href="{{ route('organization.team.index', $organization->slug) }}" class="btn btn-outline-light btn-sm">Tim</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-light btn-sm" type="submit">Odjava</button>
            </form>
        </div>
    </div>
</nav>
<div class="container py-4">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @yield('content')
</div>
</body>
</html>
