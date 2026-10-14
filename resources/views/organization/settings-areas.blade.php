@php($podrucje = request()->routeIs('organization.settings.catalog*') ? 'katalog' : 'izgled')
<ul class="nav predmet-tabovi mb-3">
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'izgled']) href="{{ route('organization.settings.appearance', $organization->slug) }}">Izgled</a>
    </li>
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'katalog']) href="{{ route('organization.settings.catalog', $organization->slug) }}">Katalog</a>
    </li>
</ul>
