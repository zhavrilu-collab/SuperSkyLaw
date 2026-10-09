(function () {
    var root = document.getElementById('officeKalendar');
    var izvorEl = document.getElementById('kalendarPodaci');
    if (!root || !izvorEl) return;

    var svi = [];
    try { svi = JSON.parse(izvorEl.textContent || '[]'); } catch (e) { svi = []; }

    var canManage = root.getAttribute('data-can-manage') === '1';
    var layout = 'mjesec';
    var view = 'kalendar';
    var fokus = new Date();
    fokus.setHours(12, 0, 0, 0);
    var zakljucaniDatum = null;
    var istaknutiId = null;

    var MJESECI = ['siječanj', 'veljača', 'ožujak', 'travanj', 'svibanj', 'lipanj', 'srpanj', 'kolovoz', 'rujan', 'listopad', 'studeni', 'prosinac'];
    var DANI = ['Ned', 'Pon', 'Uto', 'Sri', 'Čet', 'Pet', 'Sub'];
    var BOJE = {
        hearing: '#1b6b4a',
        meeting: '#3d6b8a',
        inspection: '#8a6a2f',
        appeal_deadline: '#b45309',
        objection_deadline: '#c2410c',
        limitation: '#b91c1c',
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
        return BOJE[dog.typeKey] || BOJE.other;
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

    function filtrirani() {
        return svi.filter(odgovara);
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
        return '<button type="button" class="kal-dodaj-btn" data-datum="' + datum + '" title="Novi termin">+</button>';
    }

    function traka(dog, seg) {
        var label = dog.title || '';
        if (!seg.continuesLeft && dog.time && seg.startCol === seg.endCol) label = dog.time + ' ' + label;
        var cls = 'kal-event-bar' + (dog.completed ? ' gotovo' : '');
        if (seg.continuesLeft) cls += ' kal-event-bar-cont-left';
        if (seg.continuesRight) cls += ' kal-event-bar-cont-right';
        if (seg.startCol === seg.endCol) cls += ' kal-event-bar-single';
        var title = dog.title + ' · ' + dog.type + (dog.preclusive ? ' · prekluzivno' : '');
        return '<button type="button" class="' + cls + '" data-event-id="' + dog.id + '" style="grid-column:' + (seg.startCol + 1) + ' / ' + (seg.endCol + 2) + ';grid-row:' + (seg.lane + 1) + ';--kal-bar:' + boja(dog) + ';" title="' + esc(title) + '"><span>' + esc(label) + '</span></button>';
    }

    function stupacKartica(dog) {
        var title = dog.title + (dog.matter ? ' · ' + dog.matter : '');
        return '<button type="button" class="kal-dogadjaj' + (dog.completed ? ' gotovo' : '') + '" data-event-id="' + dog.id + '" style="--kal-bar:' + boja(dog) + '"><span class="kal-dog-vrijeme">' + esc(dog.time) + '</span>' + esc(title) + '</button>';
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
            html += '<div class="kal-dan-stupac' + (jeDanas(datum) ? ' danas' : '') + '" data-datum="' + datumStr + '">'
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
        grid.innerHTML = '<div class="kal-dan-puna' + (jeDanas(fokus) ? ' danas' : '') + '" data-datum="' + datumStr + '">'
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
        var lista = filtrirani();
        grid.className = 'kalendar-grid kal-layout-' + layout;
        naslov();
        document.querySelectorAll('#kalLayoutToolbarWrap [data-kal-layout]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-kal-layout') === layout);
        });
        if (layout === 'dan') dan(grid, lista);
        else if (layout === 'tjedan') tjedan(grid, lista);
        else mjesec(grid, lista);
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
                noviNaDan(btn.getAttribute('data-datum'));
            });
        });
        grid.querySelectorAll('[data-event-id]').forEach(function (el) {
            el.addEventListener('click', function (event) {
                event.stopPropagation();
                prikaziTermin(parseInt(el.getAttribute('data-event-id'), 10));
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

    function prikaziDan(datum) {
        zakljucaniDatum = datum;
        istaknutiId = null;
        prebaciView('lista');
    }

    function prikaziTermin(id) {
        var dog = svi.find(function (item) { return item.id === id; });
        zakljucaniDatum = dog ? dog.date : null;
        istaknutiId = id;
        prebaciView('lista');
        var row = document.querySelector('#listaTermina tr[data-event-id="' + id + '"]');
        if (row) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function otvoriNoviTermin() {
        var prozor = document.getElementById('noviTerminProzor');
        if (!prozor || !window.bootstrap) return;
        window.bootstrap.Modal.getOrCreateInstance(prozor).show();
    }

    function noviNaDan(datum) {
        var input = document.getElementById('noviTerminPocetak');
        if (input) input.value = datum + 'T09:00';
        otvoriNoviTermin();
    }

    function pomakni(smjer) {
        if (layout === 'dan') fokus = dodaj(fokus, smjer);
        else if (layout === 'tjedan') fokus = dodaj(fokus, smjer * 7);
        else fokus = new Date(fokus.getFullYear(), fokus.getMonth() + smjer, 1, 12);
        osvjeziKalendar();
    }

    document.getElementById('btnKalPrethodni').addEventListener('click', function () { pomakni(-1); });
    document.getElementById('btnKalSljedeci').addEventListener('click', function () { pomakni(1); });
    document.getElementById('naslovKalendara').addEventListener('click', function () {
        if (layout === 'dan') prikaziDan(iso(fokus));
        else prebaciView('lista');
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
    var noviTerminProzor = document.getElementById('noviTerminProzor');
    if (noviTerminProzor && noviTerminProzor.getAttribute('data-otvori') === '1') otvoriNoviTermin();
    if (noviTerminProzor) {
        noviTerminProzor.addEventListener('shown.bs.modal', function () {
            var naziv = document.getElementById('noviTerminNaziv');
            if (naziv) naziv.focus();
        });
    }
})();
