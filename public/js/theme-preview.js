(function () {
    'use strict';

    var cfg = window.THEME_PREVIEW;
    if (!cfg || !cfg.palettes) return;

    function applyPalette(palette) {
        if (!palette) return;

        var root = document.documentElement;
        var vars = {
            '--primarna-zelena': palette.primary,
            '--primarna-tamna': palette.dark,
            '--svijetlo-zelena': palette.light,
            '--bordo-crvena': palette.accent,
            '--zlatna-tradicija': palette.gold,
            '--tekst-tamni': palette.text,
            '--tekst-na-primarnoj': palette.onPrimary,
            '--tema-rgb': palette.rgb,
            '--tema': palette.primary,
            '--tema-svijetla': palette.light,
            '--tema-sjena-fokus': palette.focusShadow,
            '--tema-rub-tablica': palette.tableBorder,
        };

        Object.keys(vars).forEach(function (name) {
            if (vars[name]) {
                root.style.setProperty(name, vars[name]);
            }
        });

        ['appSidebarBrandLogo', 'temaLogoPregled'].forEach(function (id) {
            var img = document.getElementById(id);
            if (img && palette.horizontalLogo) {
                img.src = palette.horizontalLogo;
            }
        });
    }

    function setPreviewMessage(themeKey) {
        var poruka = document.getElementById('temaPoruka');
        if (!poruka) return;

        if (themeKey === cfg.savedTheme) {
            poruka.textContent = '';
            poruka.className = 'ms-2 small text-muted';
            return;
        }

        var label = (cfg.palettes[themeKey] && cfg.palettes[themeKey].label) || themeKey;
        poruka.textContent = 'Pregled: ' + label + ' — klikni Spremi temu za trajno spremanje.';
        poruka.className = 'ms-2 small text-tema fw-semibold';
    }

    window.applyOrganizationThemePreview = function (themeKey) {
        var palette = cfg.palettes[themeKey];
        if (!palette) return;

        applyPalette(palette);
        setPreviewMessage(themeKey);
    };

    document.querySelectorAll('#temaBojaIzbor .tema-svatch').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var themeKey = btn.getAttribute('data-tema');
            var input = document.getElementById('themeColorInput');
            if (input) {
                input.value = themeKey;
            }

            document.querySelectorAll('#temaBojaIzbor .tema-svatch').forEach(function (el) {
                el.classList.toggle('aktivna', el === btn);
            });

            window.applyOrganizationThemePreview(themeKey);
        });
    });
})();
