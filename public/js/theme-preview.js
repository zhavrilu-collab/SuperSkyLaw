(function () {
    'use strict';

    var cfg = window.THEME_PREVIEW;
    if (!cfg || !cfg.palettes) {
        return;
    }

    var chromeVars = [
        '--gumb-pozadina',
        '--gumb-tekst',
        '--gumb-rub',
        '--gumb-debljina',
        '--gumb-hover-pozadina',
        '--gumb-hover-tekst',
        '--odabir-pozadina',
        '--odabir-tekst',
        '--odabir-crta',
        '--odabir-tezina',
        '--izbornik-pozadina',
        '--izbornik-tekst',
        '--postavke-odabir-pozadina',
        '--postavke-odabir-tekst',
        '--postavke-odabir-crta',
    ];

    function applyPalette(palette) {
        if (!palette) {
            return;
        }

        var root = document.documentElement;
        var vars = {
            '--primarna-zelena': palette.primary,
            '--primarna-tamna': palette.dark,
            '--svijetlo-zelena': palette.light,
            '--bordo-crvena': palette.accent,
            '--zlatna-tradicija': palette.gold,
            '--tekst-tamni': palette.text,
            '--tekst-na-primarnoj': palette.onPrimary,
            '--tema': palette.primary,
            '--tema-svijetla': palette.light,
            '--tema-sjena-fokus': palette.focusShadow,
            '--tema-rub-tablica': palette.tableBorder,
        };

        if (palette.rgb) {
            vars['--tema-rgb'] = palette.rgb;
        }

        Object.keys(vars).forEach(function (name) {
            if (vars[name]) {
                root.style.setProperty(name, vars[name]);
            }
        });

        if (palette.styled) {
            root.style.setProperty('--gumb-pozadina', palette.btnBg);
            root.style.setProperty('--gumb-tekst', palette.btnFg);
            root.style.setProperty('--gumb-rub', palette.btnBorder);
            root.style.setProperty('--gumb-debljina', '2px');
            root.style.setProperty('--gumb-hover-pozadina', palette.btnHoverBg);
            root.style.setProperty('--gumb-hover-tekst', palette.btnHoverFg);
            root.style.setProperty('--odabir-pozadina', palette.navBg);
            root.style.setProperty('--odabir-tekst', palette.navFg);
            root.style.setProperty('--odabir-crta', palette.navBar);
            root.style.setProperty('--odabir-tezina', palette.navWeight || '650');
            root.style.setProperty('--izbornik-pozadina', palette.sideBg || '#ffffff');
            root.style.setProperty('--izbornik-tekst', palette.idle || '#2a2a28');
            root.style.setProperty('--postavke-odabir-pozadina', palette.navBg);
            root.style.setProperty('--postavke-odabir-tekst', palette.navFg);
            root.style.setProperty('--postavke-odabir-crta', palette.navBar);
        } else {
            chromeVars.forEach(function (name) {
                root.style.removeProperty(name);
            });
        }

        var themeMeta = document.querySelector('meta[name="theme-color"]');
        if (themeMeta && palette.primary) {
            themeMeta.setAttribute('content', palette.primary);
        }

        if (!palette.horizontalLogo) {
            return;
        }

        ['appSidebarBrandLogo', 'temaLogoPregled'].forEach(function (id) {
            var img = document.getElementById(id);
            if (img) {
                img.src = palette.horizontalLogo;
            }
        });

        document.querySelectorAll('[data-product-logo="horizontal"]').forEach(function (img) {
            img.setAttribute('src', palette.horizontalLogo);
        });
    }

    function colorInput() {
        return document.getElementById('themeColorInput');
    }

    function styleInput() {
        return document.getElementById('themeStyleInput');
    }

    function currentColor() {
        return colorInput() ? colorInput().value : cfg.savedColor;
    }

    function currentStyle() {
        return styleInput() ? styleInput().value : (cfg.savedStyle || '');
    }

    function setPreviewMessage() {
        var poruka = document.getElementById('temaPoruka');
        if (!poruka) {
            return;
        }

        var color = currentColor();
        var style = currentStyle();
        var same = color === cfg.savedColor && (style || '') === (cfg.savedStyle || '');

        if (same) {
            poruka.textContent = '';
            poruka.className = 'ms-2 small text-muted';
            return;
        }

        var colorLabel = (cfg.palettes[color] && cfg.palettes[color].label) || color;
        var styleLabel = style && cfg.styles && cfg.styles[style] ? cfg.styles[style].label : '';
        var label = styleLabel ? colorLabel + ', ' + styleLabel : colorLabel;
        poruka.textContent = 'Pregled: ' + label + ' — klikni Spremi temu za trajno spremanje.';
        poruka.className = 'ms-2 small text-tema fw-semibold';
    }

    function paintCards(color) {
        document.querySelectorAll('#temaSmjerIzbor .tema-kartica').forEach(function (card) {
            var style = card.getAttribute('data-stil');
            var palette = cfg.combinations && cfg.combinations[color + '|' + style];
            if (!palette) {
                return;
            }

            var sphere = card.querySelector('.tema-kartica-kugla');
            var side = card.querySelector('.tema-kartica-strana');
            var item = card.querySelector('.tema-kartica-stavka');
            var body = card.querySelector('.tema-kartica-sadrzaj');
            var button = card.querySelector('.tema-kartica-gumb:not(.tema-kartica-obrub)');
            var outline = card.querySelector('.tema-kartica-obrub');

            if (sphere) {
                sphere.style.background = palette.logoMark || palette.primary;
            }
            if (side) {
                side.style.background = palette.sideBg || '#ffffff';
                side.style.color = palette.idle || '#2a2a28';
            }
            if (item) {
                item.style.background = palette.navBg;
                item.style.color = palette.navFg;
                item.style.borderLeftColor = palette.navBar || 'transparent';
                item.style.fontWeight = palette.navWeight || '650';
            }
            if (body) {
                body.style.background = palette.light;
                body.style.color = palette.text;
            }
            if (button) {
                button.style.background = palette.btnBg;
                button.style.color = palette.btnFg;
                button.style.borderColor = palette.btnBorder;
            }
            if (outline && palette.primary) {
                outline.style.color = palette.primary;
                outline.style.borderColor = palette.primary;
            }
        });
    }

    function previewSelection() {
        var color = currentColor();
        var style = currentStyle();
        var palette = style && cfg.combinations
            ? cfg.combinations[color + '|' + style]
            : cfg.palettes[color];

        applyPalette(palette || cfg.palettes[color]);
        paintCards(color);
        setPreviewMessage();
    }

    document.querySelectorAll('#temaBojaIzbor .tema-svatch').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var themeKey = btn.getAttribute('data-tema');
            if (colorInput()) {
                colorInput().value = themeKey;
            }

            document.querySelectorAll('#temaBojaIzbor .tema-svatch').forEach(function (el) {
                el.classList.toggle('aktivna', el === btn);
            });

            previewSelection();
        });
    });

    document.querySelectorAll('#temaSmjerIzbor .tema-kartica').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var style = btn.getAttribute('data-stil');
            if (styleInput()) {
                styleInput().value = style;
            }

            document.querySelectorAll('#temaSmjerIzbor .tema-kartica').forEach(function (el) {
                el.classList.toggle('aktivna', el === btn);
            });

            previewSelection();
        });
    });
})();
