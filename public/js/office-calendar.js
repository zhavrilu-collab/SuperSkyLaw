(function () {
    var root = document.getElementById('officeKalendar');
    var izvorEl = document.getElementById('kalendarPodaci');
    if (!root || !izvorEl) return;

    var sirovo = {};
    try { sirovo = JSON.parse(izvorEl.textContent || '{}'); } catch (e) { sirovo = {}; }
    var svi = Array.isArray(sirovo) ? sirovo : (sirovo.events || []);
    var zadaci = Array.isArray(sirovo) ? [] : (sirovo.tasks || []);
    var zapisi = Array.isArray(sirovo) ? [] : (sirovo.records || []);
    var biljeske = Array.isArray(sirovo) ? [] : (sirovo.notes || []);

    var canManage = root.getAttribute('data-can-manage') === '1';
    var layout = 'mjesec';
    var view = 'kalendar';
    var fokus = new Date();
    fokus.setHours(12, 0, 0, 0);
    var zakljucaniDatum = null;
    var istaknutiId = null;
    var panelMode = 'nadolazeci';
    var panelDan = null;
    var panelStavka = null;

    var MJESECI = ['siječanj', 'veljača', 'ožujak', 'travanj', 'svibanj', 'lipanj', 'srpanj', 'kolovoz', 'rujan', 'listopad', 'studeni', 'prosinac'];
    var DANI = ['Ned', 'Pon', 'Uto', 'Sri', 'Čet', 'Pet', 'Sub'];
    var DANI_PUNI = ['nedjelja', 'ponedjeljak', 'utorak', 'srijeda', 'četvrtak', 'petak', 'subota'];
    var BOJE = {
        hearing: '#1b6b4a',
        meeting: '#3d6b8a',
        inspection: '#8a6a2f',
        appeal_deadline: '#b45309',
        objection_deadline: '#c2410c',
        limitation: '#b91c1c',
        task: '#6d28d9',
        other: '#64748b'
    };

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function iso(date) {
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return date.getFullYear() + '-' + m + '-' + d;
    }

    function dodaj(date, days) {
        var next = new Date(date.getTime());
        next.setDate(next.getDate() + days);
        return next;
    }

    function ponedjeljak(date) {
        var day = (date.getDay() + 6) % 7;
        return dodaj(date, -day);
    }

    function jeDanas(date) {
        var now = new Date();
        return date.getFullYear() === now.getFullYear() && date.getMonth() === now.getMonth() && date.getDate() === now.getDate();
    }

    function boja(dog) {
        if (dog.kind === 'task' || dog.typeKey === 'task') return BOJE.task;
        return BOJE[dog.typeKey] || BOJE.other;
    }

    function token() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function izDatuma(isoDate) {
        var dijelovi = String(isoDate || '').split('-');
        return new Date(Number(dijelovi[0]), Number(dijelovi[1]) - 1, Number(dijelovi[2]), 12);
    }

    function puniDatum(isoDate) {
        var datum = izDatuma(isoDate);
        return DANI_PUNI[datum.getDay()] + ', ' + datum.getDate() + '. ' + MJESECI[datum.getMonth()] + ' ' + datum.getFullYear() + '.';
    }

    function jeIstaknuto(dog) {
        return panelStavka && panelStavka.kind === (dog.kind || 'event') && String(panelStavka.id) === String(dog.id);
    }

    function odabranDan(datum) {
        return panelDan === datum && panelMode !== 'nadolazeci';
    }

    function kraj(dog) {
        return dog.endDate && dog.endDate > dog.date ? dog.endDate : dog.date;
    }

    function preklapa(dog, fromIso, toIso) {
        return dog.date <= toIso && kraj(dog) >= fromIso;
    }

    function upit() {
        var input = document.getElementById('pretragaTermina');
        return input ? input.value.trim().toLowerCase() : '';
    }

    function grupa() {
        var select = document.getElementById('filterGrupa');
        return select ? select.value : 'all';
    }

    function odgovara(dog) {
        if (grupa() !== 'all' && dog.group !== grupa()) return false;
        var q = upit();
        if (!q) return true;
        var hay = (dog.title + ' ' + dog.type + ' ' + (dog.matter || '') + ' ' + (dog.court || '')).toLowerCase();
        return hay.indexOf(q) !== -1;
    }

    function odgovaraTekst(dog) {
        var q = upit();
        if (!q) return true;
        var hay = (dog.title + ' ' + (dog.type || '') + ' ' + (dog.body || '') + ' ' + (dog.matter || '') + ' ' + (dog.court || '')).toLowerCase();
        return hay.indexOf(q) !== -1;
    }

    function filtrirani() {
        return svi.filter(odgovara);
    }

    function zadaciFiltrirani() {
        if (grupa() !== 'all') return [];
        return zadaci.filter(odgovaraTekst);
    }

    function ostaliFiltrirani(lista) {
        if (grupa() !== 'all') return [];
        return lista.filter(odgovaraTekst);
    }

    function zaMrezu() {
        return filtrirani().map(function (dog) {
            dog.kind = dog.kind || 'event';
            return dog;
        }).concat(zadaciFiltrirani().filter(function (dog) {
            return !!dog.date;
        }).map(function (dog) {
            dog.kind = 'task';
            dog.endDate = dog.endDate || dog.date;
            dog.preclusive = false;
            return dog;
        }));
    }

    function zaDan(lista, datum) {
        return lista.filter(function (dog) { return preklapa(dog, datum, datum); })
            .sort(function (a, b) { return a.time < b.time ? -1 : a.time > b.time ? 1 : a.title.localeCompare(b.title, 'hr'); });
    }

    function naslov() {
        var el = document.getElementById('naslovKalendara');
        if (!el) return;
        if (layout === 'dan') {
            el.textContent = DANI[fokus.getDay()] + ', ' + fokus.getDate() + '. ' + MJESECI[fokus.getMonth()] + ' ' + fokus.getFullYear() + '.';
            return;
        }
        if (layout === 'tjedan') {
            var start = ponedjeljak(fokus);
            var end = dodaj(start, 6);
            el.textContent = start.getDate() + '. ' + MJESECI[start.getMonth()] + ' – ' + end.getDate() + '. ' + MJESECI[end.getMonth()] + ' ' + end.getFullYear() + '.';
            return;
        }
        var naziv = MJESECI[fokus.getMonth()];
        el.textContent = naziv.charAt(0).toUpperCase() + naziv.slice(1) + ' ' + fokus.getFullYear() + '.';
    }

    function plus(datum) {
        if (!canManage) return '';
        return '<button type="button" class="kal-dodaj-btn" data-unos-otvori data-kind="termin" data-datum="' + datum + '" title="Novi unos">+</button>';
    }

    function traka(dog, seg) {
        var label = dog.title || '';
        if (!seg.continuesLeft && dog.time && seg.startCol === seg.endCol) label = dog.time + ' ' + label;
        var cls = 'kal-event-bar' + (dog.completed ? ' gotovo' : '') + (jeIstaknuto(dog) ? ' istaknuto' : '');
        if (seg.continuesLeft) cls += ' kal-event-bar-cont-left';
        if (seg.continuesRight) cls += ' kal-event-bar-cont-right';
        if (seg.startCol === seg.endCol) cls += ' kal-event-bar-single';
        var title = dog.title + ' · ' + dog.type + (dog.preclusive ? ' · prekluzivno' : '');
        return '<button type="button" class="' + cls + '" data-kind="' + esc(dog.kind || 'event') + '" data-stavka-id="' + dog.id + '" data-event-id="' + dog.id + '" style="grid-column:' + (seg.startCol + 1) + ' / ' + (seg.endCol + 2) + ';grid-row:' + (seg.lane + 1) + ';--kal-bar:' + boja(dog) + ';" title="' + esc(title) + '"><span>' + esc(label) + '</span></button>';
    }

    function stupacKartica(dog) {
        var title = dog.title + (dog.matter ? ' · ' + dog.matter : '');
        return '<button type="button" class="kal-dogadjaj' + (dog.completed ? ' gotovo' : '') + (jeIstaknuto(dog) ? ' istaknuto' : '') + '" data-kind="' + esc(dog.kind || 'event') + '" data-stavka-id="' + dog.id + '" data-event-id="' + dog.id + '" style="--kal-bar:' + boja(dog) + '"><span class="kal-dog-vrijeme">' + esc(dog.time) + '</span>' + esc(title) + '</button>';
    }

    function tjedni(weekDates, lista) {
        var weekStart = weekDates[0];
        var weekEnd = weekDates[6];
        var laneEnds = [];
        var placed = [];
        lista.filter(function (dog) { return preklapa(dog, weekStart, weekEnd); }).forEach(function (dog) {
            var rawStart = dog.date < weekStart ? weekStart : dog.date;
            var rawEnd = kraj(dog) > weekEnd ? weekEnd : kraj(dog);
            var startCol = weekDates.indexOf(rawStart);
            var endCol = weekDates.indexOf(rawEnd);
            if (startCol < 0) startCol = 0;
            if (endCol < 0) endCol = 6;
            var lane = 0;
            while (lane < laneEnds.length && laneEnds[lane] >= startCol) lane++;
            laneEnds[lane] = endCol;
            placed.push({
                dog: dog,
                startCol: startCol,
                endCol: endCol,
                lane: lane,
                continuesLeft: dog.date < weekStart,
                continuesRight: kraj(dog) > weekEnd
            });
        });
        return placed;
    }

    function mjesec(grid, lista) {
        var mjesecBr = fokus.getMonth();
        var godina = fokus.getFullYear();
        var prvi = new Date(godina, mjesecBr, 1, 12);
        var zadnji = new Date(godina, mjesecBr + 1, 0, 12);
        var pomak = (prvi.getDay() + 6) % 7;
        var gridStart = dodaj(prvi, -pomak);
        var ukupno = pomak + zadnji.getDate();
        var weekCount = Math.ceil(ukupno / 7);
        var html = '<div class="kal-mjesec-head">'
            + '<div class="kal-dan-naziv">Pon</div><div class="kal-dan-naziv">Uto</div><div class="kal-dan-naziv">Sri</div>'
            + '<div class="kal-dan-naziv">Čet</div><div class="kal-dan-naziv">Pet</div><div class="kal-dan-naziv">Sub</div><div class="kal-dan-naziv">Ned</div></div>';

        for (var w = 0; w < weekCount; w++) {
            var weekDates = [];
            var i;
            for (i = 0; i < 7; i++) weekDates.push(iso(dodaj(gridStart, w * 7 + i)));
            var placed = tjedni(weekDates, lista);
            var overflow = [0, 0, 0, 0, 0, 0, 0];
            var laneCount = 1;
            placed.forEach(function (p) {
                if (p.lane < 3 && p.lane + 1 > laneCount) laneCount = p.lane + 1;
                if (p.lane < 3) return;
                for (var c = p.startCol; c <= p.endCol; c++) overflow[c]++;
            });
            html += '<div class="kal-mjesec-tjedan"><div class="kal-mjesec-dani">';
            for (i = 0; i < 7; i++) {
                var datum = dodaj(gridStart, w * 7 + i);
                var klase = 'kal-dan';
                if (datum.getMonth() !== mjesecBr) klase += ' drugi-mjesec';
                if (jeDanas(datum)) klase += ' danas';
                if (odabranDan(weekDates[i])) klase += ' odabran';
                html += '<div class="' + klase + '" data-datum="' + weekDates[i] + '"><div class="kal-dan-zaglavlje"><span class="kal-broj">' + datum.getDate() + '</span>' + plus(weekDates[i]) + '</div></div>';
            }
            html += '</div><div class="kal-mjesec-lanes" style="grid-template-rows:repeat(' + laneCount + ',auto)">';
            placed.forEach(function (p) { if (p.lane < 3) html += traka(p.dog, p); });
            html += '</div><div class="kal-mjesec-more">';
            for (i = 0; i < 7; i++) {
                html += '<div>' + (overflow[i] ? '<button type="button" class="kal-event-more" data-datum="' + weekDates[i] + '">+' + overflow[i] + ' više</button>' : '') + '</div>';
            }
            html += '</div></div>';
        }
        grid.innerHTML = html;
    }

    function tjedan(grid, lista) {
        var start = ponedjeljak(fokus);
        var html = '';
        for (var i = 0; i < 7; i++) {
            var datum = dodaj(start, i);
            var datumStr = iso(datum);
            var dogadjaji = zaDan(lista, datumStr);
            html += '<div class="kal-dan-stupac' + (jeDanas(datum) ? ' danas' : '') + (odabranDan(datumStr) ? ' odabran' : '') + '" data-datum="' + datumStr + '">'
                + '<div class="kal-stupac-naslov"><span>' + DANI[datum.getDay()] + ' ' + datum.getDate() + '.</span>' + plus(datumStr) + '</div>'
                + '<div class="kal-stupac-dogadjaji">'
                + (dogadjaji.length ? dogadjaji.map(stupacKartica).join('') : '<div class="kal-raspored-prazno">Nema termina</div>')
                + '</div></div>';
        }
        grid.innerHTML = html;
    }

    function dan(grid, lista) {
        var datumStr = iso(fokus);
        var dogadjaji = zaDan(lista, datumStr);
        grid.innerHTML = '<div class="kal-dan-puna' + (jeDanas(fokus) ? ' danas' : '') + (odabranDan(datumStr) ? ' odabran' : '') + '" data-datum="' + datumStr + '">'
            + '<div class="kal-dan-puna-zaglavlje"><span>' + DANI[fokus.getDay()] + ', ' + fokus.getDate() + '. ' + MJESECI[fokus.getMonth()] + '</span>' + plus(datumStr) + '</div>'
            + '<div class="kal-stupac-dogadjaji">'
            + (dogadjaji.length ? dogadjaji.map(stupacKartica).join('') : '<div class="kal-raspored-prazno">Nema termina za ovaj dan.</div>')
            + '</div></div>';
    }

    function legenda(lista) {
        var el = document.getElementById('kalendarLegenda');
        if (!el) return;
        var vidjeni = {};
        var html = '';
        lista.forEach(function (dog) {
            if (vidjeni[dog.type]) return;
            vidjeni[dog.type] = true;
            html += '<span><span class="kal-tocka" style="background:' + boja(dog) + '"></span> ' + esc(dog.type) + '</span>';
        });
        el.innerHTML = html;
    }

    function osvjeziKalendar() {
        var grid = document.getElementById('kalendarGrid');
        if (!grid) return;
        var lista = zaMrezu();
        grid.className = 'kalendar-grid kal-layout-' + layout;
        naslov();
        document.querySelectorAll('#kalLayoutToolbarWrap [data-kal-layout]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-kal-layout') === layout);
        });
        if (layout === 'dan') dan(grid, lista);
        else if (layout === 'tjedan') tjedan(grid, lista);
        else mjesec(grid, lista);
        osvjeziPanel();
        legenda(lista);
        grid.querySelectorAll('[data-datum]').forEach(function (el) {
            el.addEventListener('click', function (event) {
                if (event.target.closest('.kal-dodaj-btn') || event.target.closest('[data-event-id]') || event.target.closest('.kal-event-more')) return;
                prikaziDan(el.getAttribute('data-datum'));
            });
        });
        grid.querySelectorAll('.kal-dodaj-btn').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.stopPropagation();
                if (window.otvoriUnosStavke) window.otvoriUnosStavke({ kind: 'termin', datum: btn.getAttribute('data-datum') });
            });
        });
        grid.querySelectorAll('[data-stavka-id]').forEach(function (el) {
            el.addEventListener('click', function (event) {
                event.stopPropagation();
                prikaziStavku(el.getAttribute('data-kind') || 'event', el.getAttribute('data-stavka-id'));
            });
        });
        grid.querySelectorAll('.kal-event-more').forEach(function (el) {
            el.addEventListener('click', function (event) {
                event.stopPropagation();
                prikaziDan(el.getAttribute('data-datum'));
            });
        });
    }

    function osvjeziListu() {
        var q = upit();
        var g = grupa();
        var vidljivo = 0;
        document.querySelectorAll('#listaTermina tr[data-event-id]').forEach(function (row) {
            var pokazi = true;
            if (g !== 'all' && row.getAttribute('data-group') !== g) pokazi = false;
            if (zakljucaniDatum && row.getAttribute('data-date') !== zakljucaniDatum) pokazi = false;
            if (q && (row.getAttribute('data-search') || '').indexOf(q) === -1) pokazi = false;
            row.classList.toggle('d-none', !pokazi);
            row.classList.toggle('istaknuto', pokazi && istaknutiId && String(istaknutiId) === row.getAttribute('data-event-id'));
            if (pokazi) vidljivo++;
        });
        var prazno = document.getElementById('listaPrazno');
        var imaRedaka = document.querySelector('#listaTermina tr[data-event-id]');
        if (prazno) prazno.classList.toggle('d-none', !imaRedaka || vidljivo > 0);
        var opseg = document.getElementById('listaOpseg');
        if (!opseg) return;
        if (!zakljucaniDatum) {
            opseg.textContent = '';
            return;
        }
        var dijelovi = zakljucaniDatum.split('-');
        opseg.innerHTML = 'Prikaz za ' + dijelovi[2] + '.' + dijelovi[1] + '.' + dijelovi[0] + '. <button type="button" class="btn btn-link btn-sm p-0" id="ponistiDan">Svi termini</button>';
        var ponisti = document.getElementById('ponistiDan');
        if (ponisti) ponisti.addEventListener('click', function () {
            zakljucaniDatum = null;
            istaknutiId = null;
            osvjeziListu();
        });
    }

    function prebaciView(novi) {
        view = novi === 'lista' ? 'lista' : 'kalendar';
        document.getElementById('viewKalendar').classList.toggle('d-none', view !== 'kalendar');
        document.getElementById('viewLista').classList.toggle('d-none', view !== 'lista');
        document.getElementById('kalLayoutToolbarWrap').classList.toggle('d-none', view !== 'kalendar');
        document.querySelectorAll('[data-events-view]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-events-view') === view);
        });
        if (view === 'kalendar') osvjeziKalendar();
        else osvjeziListu();
    }

    function nadji(kind, id) {
        var lista = kind === 'task' ? zadaci : kind === 'record' ? zapisi : kind === 'note' ? biljeske : svi;
        return lista.find(function (item) { return String(item.id) === String(id); });
    }

    function prikaziDan(datum) {
        panelMode = 'dan';
        panelDan = datum;
        panelStavka = null;
        if (view !== 'kalendar') prebaciView('kalendar');
        else osvjeziKalendar();
    }

    function prikaziStavku(kind, id) {
        var dog = nadji(kind, id);
        panelMode = 'detalj';
        panelStavka = { kind: kind, id: String(id) };
        if (dog && dog.date) panelDan = dog.date;
        if (view !== 'kalendar') prebaciView('kalendar');
        else osvjeziKalendar();
    }

    function prikaziNadolazece() {
        panelMode = 'nadolazeci';
        panelDan = null;
        panelStavka = null;
        if (view === 'kalendar') osvjeziKalendar();
    }

    function kartica(dog) {
        var meta = dog.type || '';
        if (dog.matter) meta += (meta ? ' · ' : '') + dog.matter;
        return '<button type="button" class="kal-panel-stavka' + (dog.completed ? ' gotovo' : '') + '" data-kind="' + esc(dog.kind || 'event') + '" data-stavka-id="' + dog.id + '" style="--kal-bar:' + boja(dog) + '"><span class="vrijeme">' + esc(dog.time || '') + '</span><span class="naziv">' + esc(dog.title) + '</span><span class="meta">' + esc(meta) + '</span></button>';
    }

    function red(naziv, vrijednost) {
        if (!vrijednost) return '';
        return '<dt>' + esc(naziv) + '</dt><dd>' + vrijednost + '</dd>';
    }

    function poveznica(dog) {
        if (!dog.matterUrl) return esc(dog.matter || '');
        var tekst = dog.matter || '';
        if (dog.matterTitle) tekst += ' — ' + dog.matterTitle;
        return '<a href="' + esc(dog.matterUrl) + '">' + esc(tekst) + '</a>';
    }

    function forma(action, unutarnje) {
        return '<form method="POST" action="' + esc(action) + '" class="mt-2 d-flex gap-2 align-items-end flex-wrap"><input type="hidden" name="_token" value="' + esc(token()) + '">' + unutarnje + '</form>';
    }

    function akcije(dog) {
        if (!canManage) return '';
        var html = '';
        if ((dog.kind === 'event' || !dog.kind) && !dog.completed) {
            html += forma(root.getAttribute('data-kalendar-url') + '/' + dog.id + '/zavrsi', '<button class="kal-akcija" type="submit">Obavljeno</button>');
        }
        if (dog.kind === 'task' && !dog.completed) {
            html += forma(root.getAttribute('data-zadaci-url') + '/' + dog.id + '/zavrsi', '<button class="kal-akcija" type="submit">Obavljeno</button>');
        }
        if (dog.kind !== 'task' && dog.kind !== 'record' && dog.kind !== 'note' && dog.parties && dog.parties.length) {
            var opcije = dog.parties.map(function (party) {
                return '<option value="' + party.id + '">' + esc(party.name) + '</option>';
            }).join('');
            html += forma(root.getAttribute('data-kalendar-url') + '/' + dog.id + '/sms', '<select name="party_id" class="form-select form-select-sm">' + opcije + '</select><button class="kal-akcija" type="submit">SMS</button>');
        }
        return html;
    }

    function detalj(dog) {
        var html = '<button type="button" class="kal-panel-veza" data-panel-natrag>' + (panelDan ? 'Natrag na dan' : 'Nadolazeći') + '</button>';
        html += '<h2 class="kal-panel-naslov mt-2">' + esc(dog.title) + '</h2><dl class="kal-detalj">';
        html += red('Vrsta', esc(dog.type || ''));
        html += red('Kad', esc((dog.date ? dog.date.split('-').reverse().join('.') + '.' : '') + (dog.time ? ' ' + dog.time : '')));
        if (dog.matter) html += red('Predmet', poveznica(dog));
        html += red('Stadij', esc(dog.stage || ''));
        html += red('Sud', esc(dog.court || ''));
        html += red('Tko', esc(dog.responsible || ''));
        if (dog.preclusive) html += red('Rok', 'Prekluzivno');
        if (dog.completed) html += red('Stanje', 'Obavljeno');
        if (dog.client) html += red('Portal', 'Vidljivo klijentu');
        if (dog.oglasna) html += red('e-Oglasna', '<a href="' + esc(dog.oglasna) + '" target="_blank" rel="noopener">Otvori</a>');
        html += '</dl>';
        if (dog.notes) html += '<p class="mt-2 mb-0">' + esc(dog.notes) + '</p>';
        if (dog.body) html += '<p class="mt-2 mb-0">' + esc(dog.body) + '</p>';
        html += akcije(dog);
        return html;
    }

    function stavkeDana(datum) {
        var lista = [];
        zaDan(filtrirani(), datum).forEach(function (dog) { dog.kind = 'event'; lista.push(dog); });
        zadaciFiltrirani().forEach(function (dog) {
            if (dog.date === datum) { dog.kind = 'task'; lista.push(dog); }
        });
        ostaliFiltrirani(zapisi).forEach(function (dog) {
            if (dog.date === datum) { dog.kind = 'record'; lista.push(dog); }
        });
        ostaliFiltrirani(biljeske).forEach(function (dog) {
            if (dog.date === datum) { dog.kind = 'note'; lista.push(dog); }
        });
        return lista;
    }

    function osvjeziPanel() {
        var el = document.getElementById('kalendarPanel');
        if (!el || view !== 'kalendar') return;
        if (panelMode === 'detalj' && panelStavka) {
            var dog = nadji(panelStavka.kind, panelStavka.id);
            el.innerHTML = dog ? detalj(dog) : '<p class="text-muted mb-0">Stavka nije pronađena.</p>';
            return;
        }
        if (panelMode === 'dan' && panelDan) {
            var stavke = stavkeDana(panelDan);
            var html = '<button type="button" class="kal-panel-veza" data-panel-reset>Nadolazeći</button>';
            html += '<h2 class="kal-panel-naslov mt-2">' + esc(puniDatum(panelDan)) + '</h2>';
            html += stavke.length ? stavke.map(kartica).join('') : '<p class="text-muted">Nema stavki za ovaj dan.</p>';
            if (canManage) html += '<button type="button" class="kal-dodaj-btn mt-2" data-unos-otvori data-kind="termin" data-datum="' + panelDan + '" title="Novi unos">+</button>';
            el.innerHTML = html;
            return;
        }
        var danas = iso(new Date());
        var grupe = {};
        var bezRoka = [];
        filtrirani().forEach(function (dog) {
            if (kraj(dog) < danas) return;
            dog.kind = 'event';
            (grupe[dog.date] = grupe[dog.date] || []).push(dog);
        });
        zadaciFiltrirani().forEach(function (dog) {
            if (dog.completed) return;
            dog.kind = 'task';
            if (!dog.date) { bezRoka.push(dog); return; }
            if (dog.date < danas) return;
            (grupe[dog.date] = grupe[dog.date] || []).push(dog);
        });
        var dani = Object.keys(grupe).sort();
        var html = '<h2 class="kal-panel-naslov">Nadolazeći</h2>';
        if (!dani.length && !bezRoka.length) html += '<p class="text-muted mb-0">Nema nadolazećih termina.</p>';
        if (bezRoka.length) {
            html += '<div class="kal-panel-dan">Bez roka</div>' + bezRoka.map(kartica).join('');
        }
        dani.forEach(function (dan) {
            var datum = izDatuma(dan);
            html += '<div class="kal-panel-dan">' + DANI[datum.getDay()] + ' ' + datum.getDate() + '. ' + (datum.getMonth() + 1) + '.</div>';
            html += grupe[dan].map(kartica).join('');
        });
        el.innerHTML = html;
    }

    function pomakni(smjer) {
        if (layout === 'dan') fokus = dodaj(fokus, smjer);
        else if (layout === 'tjedan') fokus = dodaj(fokus, smjer * 7);
        else fokus = new Date(fokus.getFullYear(), fokus.getMonth() + smjer, 1, 12);
        osvjeziKalendar();
    }

    document.getElementById('btnKalPrethodni').addEventListener('click', function () { pomakni(-1); });
    document.getElementById('btnKalSljedeci').addEventListener('click', function () { pomakni(1); });
    document.getElementById('naslovKalendara').addEventListener('click', prikaziNadolazece);
    var panel = document.getElementById('kalendarPanel');
    if (panel) panel.addEventListener('click', function (event) {
        if (event.target.closest('[data-panel-reset]')) { prikaziNadolazece(); return; }
        if (event.target.closest('[data-panel-natrag]')) {
            if (panelDan) prikaziDan(panelDan);
            else prikaziNadolazece();
            return;
        }
        var stavka = event.target.closest('[data-stavka-id]');
        if (!stavka || event.target.closest('form')) return;
        prikaziStavku(stavka.getAttribute('data-kind') || 'event', stavka.getAttribute('data-stavka-id'));
    });
    document.getElementById('pretragaTermina').addEventListener('input', function () {
        if (view === 'kalendar') osvjeziKalendar();
        else osvjeziListu();
    });
    document.getElementById('filterGrupa').addEventListener('change', function () {
        if (view === 'kalendar') osvjeziKalendar();
        else osvjeziListu();
    });
    document.querySelectorAll('[data-events-view]').forEach(function (btn) {
        btn.addEventListener('click', function () { prebaciView(btn.getAttribute('data-events-view')); });
    });
    document.querySelectorAll('[data-kal-layout]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            layout = btn.getAttribute('data-kal-layout');
            osvjeziKalendar();
        });
    });

    prebaciView('kalendar');
})();
