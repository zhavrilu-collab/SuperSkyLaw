@php
    $podrucje = match (true) {
        request()->routeIs('organization.settings.catalog*') => 'katalog',
        request()->routeIs('organization.settings.calendar') => 'kalendar',
        default => 'izgled',
    };
@endphp
<ul class="nav predmet-tabovi mb-3">
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'izgled']) href="{{ route('organization.settings.appearance', $organization->slug) }}">Izgled</a>
    </li>
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'katalog']) href="{{ route('organization.settings.catalog', $organization->slug) }}">Katalog</a>
    </li>
    @planFeature('calendar_sync')
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'kalendar']) href="{{ route('organization.settings.calendar', $organization->slug) }}">Kalendar</a>
    </li>
    @endplanFeature
</ul>
