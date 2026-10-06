<style>
    :root {
        --primarna-zelena: #1b431c;
        --primarna-tamna: #112b12;
        --svijetlo-zelena: #f4f8f4;
        --bordo-crvena: #8b1414;
        --zlatna-tradicija: #d4af37;
        --tekst-tamni: #2b3a2b;
        --tema: var(--primarna-zelena);
        --tema-svijetla: var(--svijetlo-zelena);
        --tema-sjena-fokus: rgba(27, 67, 28, 0.15);
        --tema-rub-tablica: rgba(27, 67, 28, 0.18);
        --tema-greska-svijetla: #fff5f5;
    }

    /* === Tipografija (zadano za cijelu aplikaciju) === */
    body { background-color: #f5f7f5; font-family: 'Segoe UI', -apple-system, sans-serif; font-size: 13px; color: var(--tekst-tamni); }
    .small, small { font-size: 12px !important; }

    /* === Probni period — usklađeno s temom (ne Bootstrap alert-info) === */
    .app-header {
        position: sticky;
        top: 0;
        z-index: 1030;
    }
    .app-header > .navbar {
        position: relative;
        top: auto;
        z-index: auto;
        margin-bottom: 0 !important;
    }
    .app-header .trial-notice {
        border-top: 0;
        box-shadow: 0 1px 0 rgba(27, 67, 28, 0.08);
    }
    .trial-notice {
        background: linear-gradient(90deg, rgba(212, 175, 55, 0.16) 0%, var(--svijetlo-zelena) 32%, #eef4ee 100%);
        border-bottom: 1px solid rgba(27, 67, 28, 0.12);
        color: var(--tekst-tamni);
        font-size: 12px;
        line-height: 1.35;
    }
    .trial-notice--expired {
        background: linear-gradient(90deg, rgba(139, 20, 20, 0.08) 0%, #f7f2f2 40%, #f5f5f5 100%);
        border-bottom-color: rgba(139, 20, 20, 0.14);
    }
    .trial-notice__inner {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .45rem .85rem;
        padding: .5rem 1.25rem;
        max-width: 100%;
    }
    .trial-notice--compact .trial-notice__inner {
        padding: .4rem 1rem;
    }
    .trial-notice__label {
        display: inline-flex;
        align-items: center;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: var(--primarna-zelena);
        background: rgba(27, 67, 28, 0.08);
        border: 1px solid rgba(212, 175, 55, 0.45);
        border-radius: 999px;
        padding: .2rem .55rem;
        white-space: nowrap;
    }
    .trial-notice--expired .trial-notice__label {
        color: var(--bordo-crvena);
        background: rgba(139, 20, 20, 0.07);
        border-color: rgba(139, 20, 20, 0.28);
    }
    .trial-notice__text {
        flex: 1 1 12rem;
        min-width: 0;
        color: #3d4f3d;
    }
    .trial-notice--expired .trial-notice__text { color: #5a4545; }
    .trial-notice__text strong { color: var(--primarna-zelena); font-weight: 700; }
    .trial-notice--expired .trial-notice__text strong { color: var(--bordo-crvena); }
    .trial-notice__action {
        display: inline-flex;
        align-items: center;
        font-size: 11px;
        font-weight: 700;
        color: var(--primarna-zelena);
        text-decoration: none;
        border: 1px solid rgba(27, 67, 28, 0.28);
        border-radius: 8px;
        padding: .25rem .65rem;
        background: #fff;
        white-space: nowrap;
    }
    .trial-notice__action:hover {
        background: var(--primarna-zelena);
        border-color: var(--primarna-zelena);
        color: #fff;
    }
    .trial-notice--expired .trial-notice__action {
        color: var(--bordo-crvena);
        border-color: rgba(139, 20, 20, 0.3);
    }
    .trial-notice--expired .trial-notice__action:hover {
        background: var(--bordo-crvena);
        border-color: var(--bordo-crvena);
        color: #fff;
    }

    /* === Navigacija === */
    .bg-siletici { background: linear-gradient(135deg, var(--primarna-zelena) 0%, var(--primarna-tamna) 100%) !important; border-bottom: 3px solid var(--zlatna-tradicija); }
    nav.navbar { position: sticky; top: 0; z-index: 1030; }
    .app-header nav.navbar { position: relative; top: auto; }
    .navbar-brand.navbar-brand-org {
        font-size: 20px;
        font-weight: 600;
        font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
        line-height: 1.2;
    }
    .navbar-brand.navbar-brand-org .navbar-brand-prefix {
        text-transform: uppercase;
    }
    .navbar-brand.navbar-brand-org .navbar-brand-suffix {
        text-transform: none;
        font-weight: 600;
    }
    .navbar-brand-logo {
        max-height: 38px;
        width: auto;
        background: white;
        border-radius: 4px;
        padding: 2px;
        display: block;
    }
    .navbar-hamburger-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.4rem;
        height: 2.4rem;
        padding: 0;
        border: 1px solid rgba(212, 175, 55, 0.45);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        transition: background .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .navbar-hamburger-btn:hover,
    .navbar-hamburger-btn:focus {
        background: rgba(255, 255, 255, 0.16);
        border-color: var(--zlatna-tradicija);
        color: #fff;
        box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.25);
    }
    .navbar-hamburger-btn[aria-expanded="true"] {
        background: rgba(212, 175, 55, 0.22);
        border-color: var(--zlatna-tradicija);
        box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.35);
    }
    .navbar-hamburger-icon {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        width: 1.05rem;
        height: .72rem;
    }
    .navbar-hamburger-icon span {
        display: block;
        height: 2px;
        width: 100%;
        background: currentColor;
        border-radius: 2px;
        transition: transform .2s ease, opacity .2s ease;
        transform-origin: center;
    }
    .navbar-hamburger-btn[aria-expanded="true"] .navbar-hamburger-icon span:nth-child(1) {
        transform: translateY(4.5px) rotate(45deg);
    }
    .navbar-hamburger-btn[aria-expanded="true"] .navbar-hamburger-icon span:nth-child(2) {
        opacity: 0;
    }
    .navbar-hamburger-btn[aria-expanded="true"] .navbar-hamburger-icon span:nth-child(3) {
        transform: translateY(-4.5px) rotate(-45deg);
    }

    /* === Lijevi izbornik i gornja traka === */
    .app-nav-sprite {
        position: absolute;
        width: 0;
        height: 0;
        overflow: hidden;
    }
    .app-shell {
        display: flex;
        align-items: stretch;
        min-height: 100vh;
    }
    .app-sidebar {
        width: 16.75rem;
        flex: 0 0 16.75rem;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-right: 1px solid rgba(27, 67, 28, 0.12);
        position: sticky;
        top: 0;
        height: 100vh;
        z-index: 1040;
    }
    .app-sidebar-brand {
        display: flex;
        align-items: center;
        gap: .55rem;
        padding: .85rem .9rem;
        text-decoration: none;
        color: var(--primarna-tamna);
        font-weight: 700;
        font-size: 15px;
        border-bottom: 3px solid var(--zlatna-tradicija);
    }
    .app-sidebar-brand:hover { color: var(--primarna-zelena); }
    .app-sidebar-mark {
        width: 1.7rem;
        height: 1.7rem;
        object-fit: contain;
        flex: 0 0 auto;
    }
    .app-sidebar-brand-club { color: var(--primarna-zelena); }
    .app-sidebar-nav {
        flex: 1;
        overflow: auto;
        padding: .45rem .45rem 1.25rem;
    }
    .app-sidebar-link,
    .app-sidebar-group-link {
        display: flex;
        align-items: center;
        gap: .5rem;
        min-width: 0;
        padding: .38rem .5rem;
        border-radius: 8px;
        color: var(--tekst-tamni);
        text-decoration: none;
        font-size: 13px;
        line-height: 1.25;
    }
    .app-sidebar-link:hover,
    .app-sidebar-group-link:hover {
        background: var(--svijetlo-zelena);
        color: var(--primarna-zelena);
    }
    .app-sidebar-link.active,
    .app-sidebar-group-link.active {
        background: var(--svijetlo-zelena);
        color: var(--primarna-zelena);
        font-weight: 600;
    }
    .app-sidebar-icon {
        width: 1.05rem;
        height: 1.05rem;
        flex: 0 0 1.05rem;
        color: var(--primarna-zelena);
    }
    .app-sidebar-group-head {
        display: flex;
        align-items: center;
        gap: .1rem;
    }
    .app-sidebar-group-link { flex: 1; }
    .app-sidebar-group-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.7rem;
        height: 1.7rem;
        padding: 0;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: #5d6e5d;
        flex: 0 0 auto;
    }
    .app-sidebar-group-toggle:hover { background: var(--svijetlo-zelena); color: var(--primarna-zelena); }
    .app-sidebar-chevron {
        display: block;
        width: .38rem;
        height: .38rem;
        border-right: 1.5px solid currentColor;
        border-bottom: 1.5px solid currentColor;
        transform: rotate(-45deg);
        margin-top: -.1rem;
    }
    .app-sidebar-group.is-open > .app-sidebar-group-head .app-sidebar-chevron {
        transform: rotate(45deg);
        margin-top: .1rem;
    }
    .app-sidebar-submenu { display: none; padding-left: .7rem; }
    .app-sidebar-group.is-open > .app-sidebar-submenu { display: block; }
    .app-sidebar-backdrop { display: none; }
    .app-main {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    .app-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .55rem 1rem;
        background: #fff;
        border-bottom: 1px solid rgba(27, 67, 28, 0.1);
        position: sticky;
        top: 0;
        z-index: 1020;
    }
    .app-topbar-title {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #142016 !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .app-topbar-title span {
        color: #142016 !important;
    }
    .app-topbar-user {
        display: flex;
        align-items: center;
        gap: .75rem;
        flex: 0 0 auto;
    }
    .app-topbar .navbar-hamburger-btn {
        display: none;
        color: var(--primarna-zelena);
        background: #fff;
        border-color: rgba(27, 67, 28, 0.22);
    }
    .app-topbar .navbar-hamburger-btn:hover,
    .app-topbar .navbar-hamburger-btn:focus,
    .app-topbar .navbar-hamburger-btn[aria-expanded="true"] {
        color: var(--primarna-zelena);
        background: var(--svijetlo-zelena);
    }
    @media (max-width: 991.98px) {
        .app-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            transform: translateX(-105%);
            transition: transform .2s ease;
            box-shadow: 0 0 24px rgba(17, 43, 18, 0.16);
        }
        .app-shell.sidebar-open .app-sidebar { transform: none; }
        .app-shell.sidebar-open .app-sidebar-backdrop {
            display: block;
            position: fixed;
            inset: 0;
            background: rgba(17, 43, 18, 0.35);
            z-index: 1035;
        }
        .app-topbar .navbar-hamburger-btn { display: inline-flex; }
    }
    .navbar-modules-menu {
        min-width: 260px;
        max-height: min(80vh, 640px);
        overflow-y: auto;
        margin-top: .55rem !important;
        padding: .5rem 0 .4rem;
        border: 1px solid rgba(27, 67, 28, 0.12);
        border-radius: 10px;
        border-top: 3px solid var(--zlatna-tradicija);
        box-shadow: 0 10px 28px rgba(17, 43, 18, 0.14) !important;
    }
    .navbar-modules-menu-header {
        padding: .35rem 1rem .55rem;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #7a8a7a;
    }
    .navbar-modules-group {
        margin: 0;
    }
    .navbar-modules-group-head {
        display: flex;
        align-items: stretch;
        gap: 0;
    }
    .navbar-modules-group-head .navbar-modules-group-link {
        flex: 1;
        min-width: 0;
    }
    .navbar-modules-group-toggle {
        flex: 0 0 2.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        background: transparent;
        color: #6a7a6a;
        padding: 0;
        cursor: pointer;
        border-left: 1px solid rgba(27, 67, 28, 0.08);
    }
    .navbar-modules-group-toggle:hover,
    .navbar-modules-group-toggle:focus {
        background: #eef5ee;
        color: var(--primarna-zelena);
        outline: none;
    }
    .navbar-modules-chevron {
        display: inline-block;
        width: 0.45rem;
        height: 0.45rem;
        border-right: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        transform: rotate(45deg);
        transition: transform .18s ease;
        margin-top: -2px;
    }
    .navbar-modules-group.is-open > .navbar-modules-group-head .navbar-modules-chevron {
        transform: rotate(-135deg);
        margin-top: 3px;
    }
    .navbar-modules-submenu {
        display: none;
        padding: .15rem 0 .35rem;
        background: #f7faf7;
        border-top: 1px solid rgba(27, 67, 28, 0.06);
        border-bottom: 1px solid rgba(27, 67, 28, 0.06);
    }
    .navbar-modules-group.is-open > .navbar-modules-submenu {
        display: block;
    }
    .navbar-modules-submenu .dropdown-item {
        font-size: 12px;
        padding: .38rem 1rem .38rem 1.65rem;
        font-weight: 500;
        color: #445044;
    }
    .navbar-modules-submenu .dropdown-item:hover,
    .navbar-modules-submenu .dropdown-item:focus {
        background: #e8f0e8;
    }
    .navbar-modules-group--nested {
        margin: 0;
    }
    .navbar-modules-group--nested > .navbar-modules-group-head .navbar-modules-group-link {
        padding-left: 1.65rem;
        font-size: 12px;
        font-weight: 500;
        color: #445044;
    }
    .navbar-modules-group--nested > .navbar-modules-group-head .navbar-modules-group-link.active {
        font-weight: 700;
    }
    .navbar-modules-group--nested > .navbar-modules-group-head .navbar-modules-group-toggle {
        border-left-color: rgba(27, 67, 28, 0.06);
        color: #7a8a7a;
    }
    .navbar-modules-group--nested > .navbar-modules-submenu {
        background: #eef4ee;
        border-top-color: rgba(27, 67, 28, 0.05);
        border-bottom-color: rgba(27, 67, 28, 0.05);
    }
    .navbar-modules-group--nested > .navbar-modules-submenu .dropdown-item {
        padding-left: 2.45rem;
        font-size: 11.5px;
        color: #556055;
    }

    /* Fiskalna godina — custom dropdown (ne native select) */
    .navbar-fiscal-year-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        min-height: 2.4rem;
        padding: .35rem .7rem;
        border: 1px solid rgba(27, 67, 28, 0.22);
        border-radius: 8px;
        background: #fff8e8;
        color: #142016 !important;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .02em;
        line-height: 1;
        transition: background .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .navbar-fiscal-year-btn::after {
        margin-left: .15rem;
        border-top-color: #1c2b1c;
        vertical-align: .15em;
    }
    .navbar-fiscal-year-btn:hover,
    .navbar-fiscal-year-btn:focus,
    .navbar-fiscal-year-btn.show {
        background: var(--svijetlo-zelena);
        border-color: var(--primarna-zelena);
        color: var(--primarna-tamna);
        box-shadow: 0 0 0 2px rgba(27, 67, 28, 0.12);
    }
    .navbar-fiscal-year-menu {
        min-width: 11rem;
        margin-top: .5rem !important;
        padding: .45rem 0 .35rem;
        border: 1px solid rgba(27, 67, 28, 0.12);
        border-radius: 10px;
        border-top: 3px solid var(--zlatna-tradicija);
        box-shadow: 0 10px 28px rgba(17, 43, 18, 0.14) !important;
        overflow: hidden;
    }
    .navbar-fiscal-year-menu-header {
        padding: .25rem 1rem .45rem;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #7a8a7a;
    }
    .navbar-fiscal-year-menu .dropdown-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        width: 100%;
        padding: .5rem 1rem;
        border: 0;
        border-left: 3px solid transparent;
        border-radius: 0;
        background: transparent;
        color: var(--tekst-tamni);
        font-size: 13px;
        text-align: left;
    }
    .navbar-fiscal-year-menu .dropdown-item:hover,
    .navbar-fiscal-year-menu .dropdown-item:focus,
    .navbar-fiscal-year-menu .dropdown-item:focus-visible {
        background: #eef5ee;
        color: var(--primarna-zelena);
        border-left-color: rgba(212, 175, 55, 0.55);
        outline: none;
        box-shadow: none;
    }
    .navbar-fiscal-year-menu .dropdown-item:active,
    .navbar-fiscal-year-menu .dropdown-item.active {
        background: var(--svijetlo-zelena) !important;
        color: var(--primarna-zelena) !important;
        font-weight: 700;
        border-left-color: var(--zlatna-tradicija);
    }
    .navbar-fiscal-year-locked {
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .03em;
        text-transform: uppercase;
        color: #8a7a55;
        background: rgba(212, 175, 55, 0.16);
        border-radius: 999px;
        padding: .15rem .45rem;
    }

    .navbar-modules-user {
        padding: .25rem .75rem .55rem;
    }
    .navbar-modules-user-card {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .55rem .7rem;
        margin-bottom: .55rem;
        border-radius: 10px;
        background: linear-gradient(135deg, #f4f8f4 0%, #eef4ee 100%);
        border: 1px solid rgba(27, 67, 28, 0.08);
    }
    .navbar-modules-user-avatar {
        flex-shrink: 0;
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primarna-zelena), var(--primarna-tamna));
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        border: 2px solid rgba(212, 175, 55, 0.55);
    }
    .navbar-modules-user-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #7a8a7a;
        margin-bottom: .1rem;
    }
    .navbar-modules-user-email {
        font-size: 12px;
        font-weight: 600;
        color: var(--tekst-tamni);
        line-height: 1.25;
    }
    .btn-navbar-logout {
        border-radius: 9px !important;
        border: 1px solid rgba(27, 67, 28, 0.22) !important;
        background: #fff !important;
        color: var(--primarna-zelena) !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        padding: .4rem .75rem !important;
        transition: background .15s ease, border-color .15s ease, color .15s ease;
    }
    .btn-navbar-logout:hover,
    .btn-navbar-logout:focus {
        background: var(--primarna-zelena) !important;
        border-color: var(--primarna-zelena) !important;
        color: #fff !important;
    }

    /* Header: korisnik + odjava pored hamburgera */
    .navbar-user-bar-card {
        display: flex;
        align-items: center;
        gap: .55rem;
        max-width: 16rem;
        padding: .3rem;
        border-radius: 10px;
        background: transparent;
        border: 0;
    }
    @media (min-width: 992px) {
        .navbar-user-bar-card {
            padding: .3rem .55rem .3rem .35rem;
        }
    }
    .navbar-user-bar .navbar-modules-user-avatar {
        width: 1.85rem;
        height: 1.85rem;
        font-size: 11px;
        border-color: rgba(212, 175, 55, 0.7);
    }
    .navbar-user-bar-label {
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #3d4f3d !important;
        line-height: 1.1;
        margin-bottom: .05rem;
    }
    .navbar-user-bar-email {
        font-size: 12px;
        font-weight: 600;
        color: #142016 !important;
        line-height: 1.2;
        max-width: 12rem;
    }
    .btn-navbar-logout--header {
        border-color: rgba(27, 67, 28, 0.22) !important;
        background: #fff !important;
        color: var(--primarna-zelena) !important;
        white-space: nowrap;
    }
    .btn-navbar-logout--header:hover,
    .btn-navbar-logout--header:focus {
        background: var(--primarna-zelena) !important;
        border-color: var(--primarna-zelena) !important;
        color: #fff !important;
    }

    .navbar-modules-menu .dropdown-divider {
        margin: .35rem .75rem;
        border-top-color: rgba(27, 67, 28, 0.1);
    }
    .navbar-modules-menu .dropdown-item {
        font-size: 13px;
        padding: .5rem 1rem;
        color: var(--tekst-tamni);
        border-left: 3px solid transparent;
    }
    .navbar-modules-menu .dropdown-item:hover,
    .navbar-modules-menu .dropdown-item:focus,
    .navbar-modules-menu .dropdown-item:focus-visible {
        background: #eef5ee;
        color: var(--primarna-zelena);
        border-left-color: rgba(212, 175, 55, 0.55);
        outline: none;
        box-shadow: none;
    }
    .navbar-modules-menu .dropdown-item.active,
    .navbar-modules-menu .dropdown-item:active {
        background: var(--svijetlo-zelena) !important;
        color: var(--primarna-zelena) !important;
        font-weight: 700;
        border-left-color: var(--zlatna-tradicija);
    }

    /* === App dropdown meniji (dijeljeno: nav + članovi toolbar/red) === */
    .app-menu.dropdown-menu {
        min-width: 12rem;
        margin-top: .35rem !important;
        padding: .4rem 0 .3rem;
        border: 1px solid rgba(27, 67, 28, 0.12);
        border-radius: 10px;
        border-top: 3px solid var(--zlatna-tradicija);
        box-shadow: 0 10px 28px rgba(17, 43, 18, 0.14) !important;
        overflow: hidden;
        font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
        z-index: 1080;
    }
    .app-menu .dropdown-divider {
        margin: .35rem .75rem;
        border-top-color: rgba(27, 67, 28, 0.1);
        opacity: 1;
    }
    .app-menu .dropdown-item {
        display: block;
        width: 100%;
        padding: .48rem 1rem;
        border: 0;
        border-left: 3px solid transparent;
        border-radius: 0;
        background: transparent;
        color: var(--tekst-tamni);
        font-family: inherit;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.3;
        text-align: left;
    }
    .app-menu .dropdown-item:hover,
    .app-menu .dropdown-item:focus,
    .app-menu .dropdown-item:focus-visible {
        background: #eef5ee;
        color: var(--primarna-zelena);
        border-left-color: rgba(212, 175, 55, 0.55);
        outline: none;
        box-shadow: none;
    }
    .app-menu .dropdown-item:active,
    .app-menu .dropdown-item.active {
        background: var(--svijetlo-zelena) !important;
        color: var(--primarna-zelena) !important;
        font-weight: 700;
        border-left-color: var(--zlatna-tradicija);
    }
    .app-menu .dropdown-item:disabled,
    .app-menu .dropdown-item.disabled {
        color: #9aa89a !important;
        background: transparent !important;
        border-left-color: transparent !important;
        pointer-events: none;
        opacity: 1;
    }
    .app-menu .dropdown-item.text-danger {
        color: var(--bordo-crvena) !important;
    }
    .app-menu .dropdown-item.text-danger:hover,
    .app-menu .dropdown-item.text-danger:focus {
        background: #fff5f5;
        color: var(--bordo-crvena) !important;
        border-left-color: rgba(139, 20, 20, 0.35);
    }
    .btn-app-menu {
        border-radius: 9px !important;
        border: 1px solid rgba(27, 67, 28, 0.28) !important;
        background: #fff !important;
        color: var(--primarna-zelena) !important;
        font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        letter-spacing: .01em;
        line-height: 1.3 !important;
        padding: 4px 10px !important;
    }
    .btn-app-menu:hover,
    .btn-app-menu:focus,
    .btn-app-menu.show {
        background: var(--svijetlo-zelena) !important;
        border-color: var(--primarna-zelena) !important;
        color: var(--primarna-tamna) !important;
        box-shadow: 0 0 0 2px var(--tema-sjena-fokus);
    }
    .btn-app-menu::after {
        margin-left: .35rem;
        vertical-align: .15em;
        border-top-color: currentColor;
    }

    /* === Tabovi === */
    .nav-tabs .nav-link {
        color: #555;
        font-size: 12px;
        font-weight: 500;
        padding: 8px 14px;
    }
    .nav-tabs .nav-link.active {
        color: var(--primarna-zelena);
        font-weight: 700;
        font-size: 12px;
        border-bottom-color: var(--primarna-zelena);
    }
    .settings-subnav .nav-link {
        font-size: 12px;
        padding: 6px 12px;
        border-radius: 20px;
        color: #555;
    }
    .settings-subnav .nav-link.active {
        background: var(--primarna-zelena);
        color: #fff;
    }

    /* === Gumbi === */
    .btn {
        font-size: 13px;
        font-weight: 500;
        border-radius: 9px;
    }
    /* Mali gumbi u tablicama (▲▼ 💾 ✕ +) */
    .btn-akcija-tablica {
        padding: 1px 6px !important;
        font-size: 11px !important;
        line-height: 1.2 !important;
        border-radius: 6px !important;
        min-width: 0 !important;
        width: auto !important;
        height: auto !important;
    }
    /* Gumbi Spremi u formama — kao Stil.html / Postavke.html */
    .btn-spremi {
        padding: 6px 12px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        border-radius: 9px !important;
        line-height: 1.25 !important;
    }
    .btn-sm:not(.btn-akcija-tablica):not(.btn-spremi) {
        padding: 4px 10px !important;
        font-size: 12px !important;
        line-height: 1.3 !important;
        border-radius: 9px !important;
    }
    .btn-success {
        background-color: var(--primarna-zelena) !important;
        border-color: var(--primarna-zelena) !important;
    }
    .btn-success:hover {
        background-color: var(--primarna-tamna) !important;
        border-color: var(--primarna-tamna) !important;
    }
    .btn-outline-success { color: var(--primarna-zelena); border-color: var(--primarna-zelena); }
    .btn-outline-success:hover { background: var(--primarna-zelena); border-color: var(--primarna-zelena); color: #fff; }
    .btn-primary {
        background-color: var(--primarna-zelena) !important;
        border-color: var(--primarna-zelena) !important;
        color: #fff !important;
    }
    .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
        background-color: var(--primarna-tamna) !important;
        border-color: var(--primarna-tamna) !important;
        color: #fff !important;
    }
    .btn-outline-primary {
        color: var(--primarna-zelena) !important;
        border-color: rgba(27, 67, 28, 0.45) !important;
        background: #fff !important;
    }
    .btn-outline-primary:hover, .btn-outline-primary:focus, .btn-outline-primary:active {
        background-color: var(--primarna-zelena) !important;
        border-color: var(--primarna-zelena) !important;
        color: #fff !important;
    }
    .text-bg-primary { background-color: var(--primarna-zelena) !important; }

    /* === List group / linkovi (bez Bootstrap plave) === */
    .list-group-item.active {
        background-color: var(--primarna-zelena) !important;
        border-color: var(--primarna-zelena) !important;
        color: #fff !important;
    }
    .list-group-item.active .badge {
        background-color: #fff !important;
        color: var(--primarna-zelena) !important;
        border-color: transparent !important;
    }
    .list-group-item-action:hover:not(.active),
    .list-group-item-action:focus:not(.active) {
        background-color: var(--svijetlo-zelena);
        color: var(--tekst-tamni);
    }
    .btn-link {
        color: var(--primarna-zelena) !important;
    }
    .btn-link:hover, .btn-link:focus {
        color: var(--primarna-tamna) !important;
    }

    /* === Accordion (bez Bootstrap plave) === */
    .accordion-button {
        background-color: #fff;
        color: var(--tekst-tamni);
        font-size: 12px;
        box-shadow: none !important;
    }
    .accordion-button:not(.collapsed) {
        background-color: var(--svijetlo-zelena) !important;
        color: var(--primarna-zelena) !important;
    }
    .accordion-button:focus {
        border-color: rgba(27, 67, 28, 0.2);
        box-shadow: 0 0 0 0.2rem rgba(27, 67, 28, 0.12) !important;
    }
    .accordion-button::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%231b431c'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e") !important;
    }

    /* === Forme === */
    .form-label { font-size: 12px; margin-bottom: 4px; }
    .form-control-sm, .form-select-sm { font-size: 12px; }
    .form-control:focus, .form-select:focus {
        border-color: var(--primarna-zelena) !important;
        box-shadow: 0 0 0 3px var(--tema-sjena-fokus) !important;
        outline: 0 !important;
    }

    /* === Poruke (uspjeh / greška) === */
    .alert-tema {
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        padding: 12px 16px;
        border-width: 1px;
        border-style: solid;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .alert-tema .alert-tema-ikona {
        font-size: 16px;
        line-height: 1.2;
        flex-shrink: 0;
    }
    .alert-success.alert-tema {
        background-color: var(--svijetlo-zelena) !important;
        border-color: var(--primarna-zelena) !important;
        color: var(--primarna-tamna) !important;
    }
    .alert-danger.alert-tema {
        background-color: var(--tema-greska-svijetla) !important;
        border-color: var(--bordo-crvena) !important;
        color: var(--bordo-crvena) !important;
    }
    .alert-danger.alert-tema ul { color: inherit; }
    .alert-warning.alert-tema {
        background-color: #fff8e8 !important;
        border-color: var(--zlatna-tradicija) !important;
        color: #6b5200 !important;
    }

    .flash-toast-kontejner {
        position: fixed;
        top: 72px;
        right: 16px;
        z-index: 1080;
        width: min(420px, calc(100vw - 32px));
        pointer-events: none;
    }
    .flash-toast-kontejner .flash-toast {
        pointer-events: auto;
        margin: 0 0 10px;
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.14);
        opacity: 1;
        transform: translateY(0);
        transition: opacity 0.28s ease, transform 0.28s ease;
    }
    .flash-toast-kontejner .flash-toast.flash-toast-hide {
        opacity: 0;
        transform: translateY(-6px);
    }

    /* === Checkboxovi (zelena tema — globalno) === */
    .form-check-input {
        width: 14px;
        height: 14px;
        margin-top: 0.15em;
        border-color: #b9c4b9;
        cursor: pointer;
    }
    .form-check-input:checked {
        background-color: var(--primarna-zelena);
        border-color: var(--primarna-zelena);
    }
    .form-check-input:focus {
        border-color: var(--primarna-zelena);
        box-shadow: 0 0 0 0.2rem rgba(27, 67, 28, 0.2);
    }
    .form-check-label { font-size: 12px; cursor: pointer; }

    /* === Kartice i tablice === */
    .text-tema { color: var(--primarna-zelena) !important; }
    .kartica-kontejner {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.02);
        border: 1px solid rgba(0,0,0,0.04);
    }
    .table th,
    .table thead.table-light th {
        font-weight: 600;
        font-size: 12px;
        color: var(--primarna-zelena) !important;
        background-color: var(--svijetlo-zelena) !important;
        padding: 8px 6px !important;
        vertical-align: middle;
    }
    .table td { padding: 6px !important; vertical-align: middle; font-size: 12px; }

    /* === Freezano zaglavlje tablica (scroll unutar tablice) === */
    .table-responsive:not(.table-responsive-no-sticky),
    .tablica-sticky {
        max-height: min(70vh, calc(100vh - 11rem));
        overflow: auto;
    }
    .table-responsive:not(.table-responsive-no-sticky) > .table > thead > tr > th,
    .table-responsive:not(.table-responsive-no-sticky) > table > thead > tr > th,
    .tablica-sticky > .table > thead > tr > th,
    .tablica-sticky > table > thead > tr > th,
    .tablica-sticky thead th {
        position: sticky;
        top: 0;
        z-index: 4;
        background-color: var(--svijetlo-zelena) !important;
        box-shadow: 0 1px 0 var(--tema-rub-tablica);
    }
    /* Nested helper tables: skip sticky viewport scroll box */
    .table-responsive.table-responsive-no-sticky,
    .table-responsive-no-sticky {
        max-height: none;
        overflow: visible;
    }
    .table-responsive.table-responsive-no-sticky {
        overflow-x: auto;
    }

    .guest-shell { min-height: 100vh; background: #f5f7f5; }
    .guest-shell .card { border: 1px solid rgba(0,0,0,.04); border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,.04); }
    .app-brand-lockup { display: flex; align-items: center; justify-content: center; gap: .55rem; font-weight: 700; font-size: 22px; color: #112b12; margin-bottom: 1rem; letter-spacing: -.01em; }
    .app-brand-mark { width: 2rem; height: 2rem; object-fit: contain; flex: 0 0 auto; }
    .app-brand-lockup span { color: var(--primarna-zelena); }
    .pocetna-mreza { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    @media (min-width: 992px) { .pocetna-mreza { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .kartica-modula { background: white; border-radius: 14px; padding: 22px 14px; text-align: center; box-shadow: 0 5px 18px rgba(0,0,0,0.07); border: 2px solid transparent; transition: all 0.25s ease; text-decoration: none; color: inherit; display: block; height: 100%; }
    .kartica-modula:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(0,0,0,0.12); border-color: var(--primarna-zelena); color: inherit; }
    .kartica-modula h3 { font-size: 1.05rem; font-weight: 700; color: var(--primarna-zelena); margin: 0 0 6px; }
    .kartica-modula p { color: #666; font-size: 0.78rem; margin: 0; line-height: 1.4; }
</style>
