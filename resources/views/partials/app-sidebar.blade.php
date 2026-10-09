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

    @perm('time.view')
    <a class="app-sidebar-link @if(request()->routeIs('organization.time.*')) active @endif" href="{{ route('organization.time.index', $slug) }}">@include('partials.nav-icon', ['name' => 'clock'])Vrijeme</a>
    @endperm

    @perm('finance.view')
    @php $financeOpen = request()->routeIs('organization.invoices.*', 'organization.expenses.*', 'organization.tariff.*', 'organization.cost-bills.*', 'organization.reports.*', 'organization.trust.*'); @endphp
    <div class="app-sidebar-group @if($financeOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($financeOpen) active @endif" href="{{ route('organization.invoices.index', $slug) }}">@include('partials.nav-icon', ['name' => 'coins'])Financije</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $financeOpen ? 'true' : 'false' }}"><span class="app-sidebar-chevron"></span></button>
        </div>
        <div class="app-sidebar-submenu">
            <a class="app-sidebar-link @if(request()->routeIs('organization.invoices.*', 'organization.expenses.*')) active @endif" href="{{ route('organization.invoices.index', $slug) }}">@include('partials.nav-icon', ['name' => 'banknote'])Računi</a>
            @planFeature('tariff_hok')
            <a class="app-sidebar-link @if(request()->routeIs('organization.tariff.*')) active @endif" href="{{ route('organization.tariff.index', $slug) }}">@include('partials.nav-icon', ['name' => 'file'])Tarifa HOK</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.cost-bills.*')) active @endif" href="{{ route('organization.cost-bills.index', $slug) }}">@include('partials.nav-icon', ['name' => 'file'])Troškovnik</a>
            @endplanFeature
            <a class="app-sidebar-link @if(request()->routeIs('organization.reports.*')) active @endif" href="{{ route('organization.reports.index', $slug) }}">@include('partials.nav-icon', ['name' => 'sliders'])Izvještaj</a>
            <a class="app-sidebar-link @if(request()->routeIs('organization.trust.*')) active @endif" href="{{ route('organization.trust.index', $slug) }}">@include('partials.nav-icon', ['name' => 'banknote'])Depozit</a>
        </div>
    </div>
    @endperm

    @perm('documents.view')
    @php $docsOpen = request()->routeIs('organization.documents.*', 'organization.templates.*'); @endphp
    <div class="app-sidebar-group @if($docsOpen) is-open @endif">
        <div class="app-sidebar-group-head">
            <a class="app-sidebar-group-link @if($docsOpen) active @endif" href="{{ route('organization.documents.index', $slug) }}">@include('partials.nav-icon', ['name' => 'folder'])Dokumenti</a>
            <button type="button" class="app-sidebar-group-toggle" aria-expanded="{{ $docsOpen ? 'true' : 'false' }}"><span class="app-sidebar-chevron"></span></button>
        </div>
        <div class="app-sidebar-submenu">
            <a class="app-sidebar-link @if(request()->routeIs('organization.documents.index', 'organization.documents.download')) active @endif" href="{{ route('organization.documents.index', $slug) }}">@include('partials.nav-icon', ['name' => 'folder'])Mape</a>
            @planFeature('document_templates')
            <a class="app-sidebar-link @if(request()->routeIs('organization.templates.*')) active @endif" href="{{ route('organization.templates.index', $slug) }}">@include('partials.nav-icon', ['name' => 'file'])Predlošci</a>
            @endplanFeature
        </div>
    </div>
    @endperm

    @perm('settings.manage')
    <a class="app-sidebar-link @if(request()->routeIs('organization.settings.appearance')) active @endif" href="{{ route('organization.settings.appearance', $slug) }}">@include('partials.nav-icon', ['name' => 'gear'])Postavke</a>
    @endperm
</nav>
