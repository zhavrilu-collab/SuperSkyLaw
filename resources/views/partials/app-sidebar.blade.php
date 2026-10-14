@include('partials.nav-icons')
@php
    $slug = $org->slug;
@endphp
<nav class="app-sidebar-nav" aria-label="Moduli">
    <a class="app-sidebar-link @if(request()->routeIs('organization.dashboard')) active @endif" href="{{ route('organization.dashboard', $slug) }}">@include('partials.nav-icon', ['name' => 'home'])Početna</a>

    @perm('settings.manage')
    <a class="app-sidebar-link @if(request()->routeIs('organization.settings.edit', 'organization.team.*')) active @endif" href="{{ route('organization.settings.edit', $slug) }}">@include('partials.nav-icon', ['name' => 'building'])Ured</a>
    @endperm

    @perm('matters.view')
    <a class="app-sidebar-link @if(request()->routeIs('organization.matters.*')) active @endif" href="{{ route('organization.matters.index', $slug) }}">@include('partials.nav-icon', ['name' => 'briefcase'])Predmeti</a>
    @endperm

    @perm('parties.view')
    <a class="app-sidebar-link @if(request()->routeIs('organization.parties.*')) active @endif" href="{{ route('organization.parties.index', $slug) }}">@include('partials.nav-icon', ['name' => 'people'])Stranke</a>
    @endperm

    @perm('calendar.view')
    <a class="app-sidebar-link @if(request()->routeIs('organization.calendar.*')) active @endif" href="{{ route('organization.calendar.index', $slug) }}">@include('partials.nav-icon', ['name' => 'calendar'])Kalendar</a>
    @endperm

    @planFeature('email_intake')
    @perm('matters.manage')
    <a class="app-sidebar-link @if(request()->routeIs('organization.mail.*')) active @endif" href="{{ route('organization.mail.index', $slug) }}">@include('partials.nav-icon', ['name' => 'inbox'])Pošta</a>
    @endperm
    @endplanFeature

    @perm('finance.view')
    <a class="app-sidebar-link @if(request()->routeIs('organization.invoices.*', 'organization.expenses.*', 'organization.time.*', 'organization.tariff.*', 'organization.cost-bills.*', 'organization.reports.*', 'organization.trust.*')) active @endif" href="{{ route('organization.invoices.index', $slug) }}">@include('partials.nav-icon', ['name' => 'coins'])Financije</a>
    @endperm

    @perm('documents.view')
    <a class="app-sidebar-link @if(request()->routeIs('organization.documents.*', 'organization.templates.*')) active @endif" href="{{ route('organization.documents.index', $slug) }}">@include('partials.nav-icon', ['name' => 'folder'])Dokumenti</a>
    @endperm

    @perm('matters.view')
    <a class="app-sidebar-link @if(request()->routeIs('organization.statutes.*')) active @endif" href="{{ route('organization.statutes.index', $slug) }}">@include('partials.nav-icon', ['name' => 'file'])Zakoni</a>
    @endperm

    @perm('settings.manage')
    <a class="app-sidebar-link @if(request()->routeIs('organization.settings.appearance', 'organization.settings.catalog*')) active @endif" href="{{ route('organization.settings.appearance', $slug) }}">@include('partials.nav-icon', ['name' => 'gear'])Postavke</a>
    @endperm
</nav>
