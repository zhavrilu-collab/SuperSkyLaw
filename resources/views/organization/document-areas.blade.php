@php($podrucje = request()->routeIs('organization.templates.*') ? 'predlosci' : 'mape')
<ul class="nav predmet-tabovi mb-3">
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'mape']) href="{{ route('organization.documents.index', $org->slug) }}">Mape</a>
    </li>
    @planFeature('document_templates')
    <li class="nav-item">
        <a @class(['nav-link', 'active' => $podrucje === 'predlosci']) href="{{ route('organization.templates.index', $org->slug) }}">Predlošci</a>
    </li>
    @endplanFeature
</ul>
