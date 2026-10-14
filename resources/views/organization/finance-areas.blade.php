@php
    $podrucje = match (true) {
        request()->routeIs('organization.time.*') => 'sati',
        request()->routeIs('organization.tariff.*') => 'tarifa',
        request()->routeIs('organization.cost-bills.*') => 'troskovnik',
        request()->routeIs('organization.reports.*') => 'izvjestaj',
        request()->routeIs('organization.trust.*') => 'depozit',
        default => 'racuni',
    };
@endphp
<ul class="nav predmet-tabovi mb-3">
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'racuni']) href="{{ route('organization.invoices.index', $org->slug) }}">Računi</a>
    </li>
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'sati']) href="{{ route('organization.time.index', $org->slug) }}">Sati</a>
    </li>
    @planFeature('tariff_hok')
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'tarifa']) href="{{ route('organization.tariff.index', $org->slug) }}">Tarifa HOK</a>
    </li>
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'troskovnik']) href="{{ route('organization.cost-bills.index', $org->slug) }}">Troškovnik</a>
    </li>
    @endplanFeature
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'izvjestaj']) href="{{ route('organization.reports.index', $org->slug) }}">Izvještaj</a>
    </li>
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'depozit']) href="{{ route('organization.trust.index', $org->slug) }}">Depozit</a>
    </li>
</ul>
