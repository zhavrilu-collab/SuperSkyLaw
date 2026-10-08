@extends('layouts.guest-login')

@section('title', 'Registracija ureda — SuperSkyLaw')
@section('tagline', 'Platforma za upravljanje odvjetničkim društvima.')
@section('shell-width', 'col-lg-8')

@section('content')
<h1 class="h4 mb-2">Registracija ureda</h1>
<p class="text-muted small">Nakon registracije ured čeka odobrenje super-administratora. Podaci se povlače iz imenika Hrvatske odvjetničke komore, a za društvo i podružnicu i iz sudskog registra.</p>

@if ($errors->any())
    <div class="alert alert-danger small">
        <strong>Registracija nije spremljena.</strong> Ispravite označena polja.
    </div>
@endif

<form method="POST" action="{{ route('register.organization') }}" id="officeRegistration">
    @csrf

    <h2 class="h6">Pronađi ured</h2>
    <label class="form-label" for="registryLookupQ">Naziv ili grad</label>
    <input type="search" id="registryLookupQ" class="form-control" placeholder="npr. Anić ili Split" autocomplete="off">
    <div class="form-text" id="registryLookupStatus">
        @if(($directoryCount ?? 0) > 0)
            U imeniku je {{ number_format($directoryCount, 0, ',', '.') }} ureda.
        @else
            Imenik još nije učitan. Ured možete upisati ručno.
        @endif
    </div>
    <div id="registryLookupResults" class="list-group my-2"></div>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="registryManual">Upiši ručno</button>
        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="registryUnlock">Ispravi podatke iz registra</button>
    </div>

    <h2 class="h6">Podaci ureda</h2>
    <div class="mb-3">
        <label class="form-label" for="office_kind">Oblik</label>
        <select name="office_kind" id="office_kind" class="form-select @error('office_kind') is-invalid @enderror" required>
            <option value="" @selected(old('office_kind') === null)>Odaberite oblik</option>
            @foreach($officeKinds as $kind)
                <option value="{{ $kind->value }}" @selected(old('office_kind') === $kind->value)>{{ $kind->label() }}</option>
            @endforeach
        </select>
        @error('office_kind')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label" for="name">Naziv</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label" for="oib">OIB</label>
            <input type="text" name="oib" id="oib" class="form-control @error('oib') is-invalid @enderror" value="{{ old('oib') }}" inputmode="numeric" maxlength="11" required>
            <div class="form-text" id="oibHint">Javni imenik ne objavljuje OIB. Za samostalni i zajednički ured upišite ga sami.</div>
            @error('oib')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div id="courtMatches" class="list-group mt-2"></div>
        </div>
        <div class="col-md-6 mb-3 d-none" id="mbsRow">
            <label class="form-label" for="mbs">MBS</label>
            <input type="text" id="mbs" class="form-control" value="{{ old('mbs') }}" readonly>
            <div class="form-text">Iz sudskog registra, nije za unos.</div>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="address">Adresa</label>
        <input type="text" name="address" id="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" required>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label" for="city">Grad</label>
            <input type="text" name="city" id="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}" required>
            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label" for="phone">Telefon</label>
            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <h2 class="h6">Račun</h2>
    <p class="text-muted small">E-mail i IBAN nisu u javnim registrima.</p>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label" for="organization_email">E-mail ureda</label>
            <input type="email" name="organization_email" id="organization_email" class="form-control @error('organization_email') is-invalid @enderror" value="{{ old('organization_email') }}" required>
            @error('organization_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label" for="iban">Poslovni IBAN</label>
            <input type="text" name="iban" id="iban" class="form-control @error('iban') is-invalid @enderror" value="{{ old('iban') }}" required>
            @error('iban')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    @unless($isLoggedIn)
        <hr>
        <h2 class="h6">Osoba koja otvara ured</h2>
        <div class="mb-3">
            <label class="form-label" for="admin_name">Ime i prezime</label>
            <input type="text" name="admin_name" id="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" required>
            @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="admin_email">E-mail</label>
            <input type="email" name="admin_email" id="admin_email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email') }}" required>
            @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Lozinka</label>
            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirmation">Potvrda lozinke</label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
        </div>
    @endunless

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <a href="{{ route('login') }}">Natrag na prijavu</a>
        <button type="submit" class="btn btn-login">Registriraj ured</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const lookupUrl = @json($directoryLookupUrl);
    const courtUrl = @json($courtLookupUrl);
    const courtKinds = @json($courtKinds);
    const soleKind = @json($soleKind);
    const qInput = document.getElementById('registryLookupQ');
    const resultsEl = document.getElementById('registryLookupResults');
    const statusEl = document.getElementById('registryLookupStatus');
    const kindInput = document.getElementById('office_kind');
    const nameInput = document.getElementById('name');
    const oibInput = document.getElementById('oib');
    const mbsInput = document.getElementById('mbs');
    const mbsRow = document.getElementById('mbsRow');
    const oibHint = document.getElementById('oibHint');
    const courtMatches = document.getElementById('courtMatches');
    const unlockBtn = document.getElementById('registryUnlock');
    const adminName = document.getElementById('admin_name');
    let adminTouched = adminName ? adminName.value !== '' : true;
    let courtTimer = null;

    if (adminName) {
        adminName.addEventListener('input', function () { adminTouched = true; });
    }

    function usesCourt() {
        return courtKinds.indexOf(kindInput.value) !== -1;
    }

    function setLocked(id, value, lock) {
        const el = document.getElementById(id);
        if (!el) return;
        if (value) el.value = value;
        el.readOnly = Boolean(lock && el.value);
    }

    function unlock() {
        ['name', 'address', 'city', 'phone', 'oib'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.readOnly = false;
        });
        unlockBtn.classList.add('d-none');
    }

    function showMbs(visible) {
        mbsRow.classList.toggle('d-none', !visible);
        if (!visible) mbsInput.value = '';
        oibHint.textContent = visible
            ? 'OIB, MBS i sjedište dolaze iz sudskog registra.'
            : 'Javni imenik ne objavljuje OIB. Upišite ga sami.';
    }

    function renderCourtMatches(matches) {
        courtMatches.innerHTML = '';
        matches.forEach(function (match) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action small';
            button.textContent = match.name + (match.city ? ' · ' + match.city : '') + (match.oib ? ' · OIB ' + match.oib : '');
            button.addEventListener('click', function () { applyCourt(match); });
            courtMatches.appendChild(button);
        });
    }

    function applyCourt(match) {
        setLocked('name', match.name, true);
        setLocked('oib', match.oib, true);
        setLocked('address', match.address, true);
        setLocked('city', match.city, true);
        mbsInput.value = match.mbs || '';
        courtMatches.innerHTML = '';
        statusEl.textContent = 'Podaci društva su iz sudskog registra.';
        unlockBtn.classList.remove('d-none');
    }

    function lookupCourt() {
        if (!usesCourt() || nameInput.value.trim().length < 2) return;
        const params = new URLSearchParams({ name: nameInput.value.trim(), office_kind: kindInput.value });
        fetch(courtUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json().then(function (body) { return { ok: response.ok, body: body }; }); })
            .then(function (result) {
                if (!result.ok) {
                    statusEl.textContent = result.body.message || 'Sudski registar nije vratio subjekt.';
                    return;
                }
                const matches = result.body.matches || [];
                if (matches.length === 1) {
                    applyCourt(matches[0]);
                } else if (matches.length > 1) {
                    renderCourtMatches(matches);
                    statusEl.textContent = 'Odaberite subjekt iz sudskog registra.';
                } else {
                    statusEl.textContent = 'Sudski registar nema subjekt za taj naziv. Upišite OIB.';
                }
            })
            .catch(function () {
                statusEl.textContent = 'Sudski registar trenutno nije dostupan.';
            });
    }

    function applyDirectory(entry) {
        kindInput.value = entry.office_kind;
        showMbs(usesCourt());
        setLocked('name', entry.name, true);
        setLocked('address', entry.address, true);
        setLocked('city', entry.city, true);
        setLocked('phone', entry.phone, Boolean(entry.phone));
        oibInput.value = '';
        oibInput.readOnly = false;
        mbsInput.value = '';
        resultsEl.innerHTML = '';
        unlockBtn.classList.remove('d-none');
        if (adminName && !adminTouched && entry.office_kind === soleKind) {
            adminName.value = entry.name;
        }
        if (usesCourt()) lookupCourt();
        else statusEl.textContent = 'Podaci su iz imenika. OIB upišite sami.';
    }

    function renderResults(results) {
        resultsEl.innerHTML = '';
        results.forEach(function (entry) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action';
            const title = document.createElement('div');
            title.textContent = entry.name;
            const meta = document.createElement('div');
            meta.className = 'small text-muted';
            meta.textContent = entry.office_kind_label + (entry.city ? ' · ' + entry.city : '');
            button.appendChild(title);
            button.appendChild(meta);
            button.addEventListener('click', function () { applyDirectory(entry); });
            resultsEl.appendChild(button);
        });
    }

    let timer = null;
    qInput.addEventListener('input', function () {
        clearTimeout(timer);
        const q = qInput.value.trim();
        if (q.length < 2) {
            resultsEl.innerHTML = '';
            return;
        }
        timer = setTimeout(function () {
            fetch(lookupUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (response) { return response.json(); })
                .then(function (body) {
                    const results = body.results || [];
                    renderResults(results);
                    statusEl.textContent = results.length ? 'Odaberite ured.' : 'Nema pogotka. Ured možete upisati ručno.';
                })
                .catch(function () {
                    statusEl.textContent = 'Pretraga imenika trenutno nije dostupna.';
                });
        }, 300);
    });

    document.getElementById('registryManual').addEventListener('click', function () {
        unlock();
        ['name', 'address', 'city', 'phone', 'oib'].forEach(function (id) {
            document.getElementById(id).value = '';
        });
        mbsInput.value = '';
        kindInput.value = '';
        showMbs(false);
        resultsEl.innerHTML = '';
        courtMatches.innerHTML = '';
        statusEl.textContent = 'Upišite podatke ureda. Za podružnicu stranog društva odaberite oblik i naziv.';
        kindInput.focus();
    });

    unlockBtn.addEventListener('click', unlock);

    kindInput.addEventListener('change', function () {
        showMbs(usesCourt());
        if (usesCourt()) lookupCourt();
    });

    nameInput.addEventListener('input', function () {
        if (nameInput.readOnly || !usesCourt()) return;
        clearTimeout(courtTimer);
        courtTimer = setTimeout(lookupCourt, 400);
    });

    showMbs(usesCourt());
})();
</script>
@endpush
