(function () {
    var prozor = document.getElementById('unosStavkeProzor');
    var forma = document.getElementById('unosStavkeForm');
    var vrsta = document.getElementById('unosVrsta');
    var predmet = document.getElementById('unosPredmet');
    var stadij = document.getElementById('unosStadij');
    var izvor = document.getElementById('unosStadiji');
    if (!prozor || !forma || !vrsta || !predmet || !stadij) return;

    var stadiji = [];
    try { stadiji = JSON.parse(izvor ? izvor.textContent || '[]' : '[]'); } catch (e) { stadiji = []; }

    function blokovi(ime) {
        return Array.prototype.filter.call(prozor.querySelectorAll('[data-unos]'), function (blok) {
            return blok.getAttribute('data-unos').split(' ').indexOf(ime) !== -1;
        });
    }

    function osvjeziStadije(odabrani) {
        var matterId = predmet.value;
        var html = '<option value="">Bez stadija</option>';
        stadiji.forEach(function (row) {
            if (String(row.matter_id) !== String(matterId)) return;
            var selected = String(row.id) === String(odabrani || '') ? ' selected' : '';
            html += '<option value="' + row.id + '"' + selected + '>' + String(row.name).replace(/[&<>]/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c];
            }) + '</option>';
        });
        stadij.innerHTML = html;
        if (odabrani) stadij.value = String(odabrani);
    }

    function primijeni() {
        var ime = vrsta.value;
        prozor.setAttribute('data-vrsta', ime);
        prozor.querySelectorAll('[data-unos]').forEach(function (blok) {
            var vidljiv = blok.getAttribute('data-unos').split(' ').indexOf(ime) !== -1;
            blok.hidden = !vidljiv;
            blok.querySelectorAll('input, select, textarea').forEach(function (el) {
                el.disabled = !vidljiv;
            });
        });
        var tijelo = document.getElementById('unosTijelo');
        var kad = document.getElementById('unosKad');
        var prazan = predmet.querySelector('option[value=""]');
        var trebaPredmet = ime === 'zapis' || ime === 'biljeska';
        predmet.required = trebaPredmet;
        if (prazan) prazan.textContent = trebaPredmet ? 'Odaberite predmet' : 'Bez predmeta';
        if (tijelo) tijelo.required = ime === 'zapis' || ime === 'biljeska';
        if (kad) kad.required = ime === 'zapis';
    }

    function postavi(polje, vrijednost) {
        if (!polje || vrijednost == null || vrijednost === '') return;
        polje.value = vrijednost;
    }

    function otvori(podaci) {
        podaci = podaci || {};
        if (podaci.kind && vrsta.querySelector('option[value="' + podaci.kind + '"]')) vrsta.value = podaci.kind;
        if (podaci.matter != null) predmet.value = podaci.matter;
        osvjeziStadije(podaci.stage || '');
        if (podaci.user) {
            postavi(document.getElementById('unosOdgovorni'), podaci.user);
            postavi(document.getElementById('unosIzvrsitelj'), podaci.user);
        }
        if (podaci.datum) {
            var dan = String(podaci.datum).slice(0, 10);
            var sat = dan + 'T09:00';
            postavi(document.getElementById('unosPocetak'), sat);
            postavi(document.getElementById('unosRok'), sat);
            postavi(document.getElementById('unosKad'), sat);
            postavi(document.getElementById('unosDatumBiljeske'), dan);
        }
        primijeni();
        if (window.bootstrap) window.bootstrap.Modal.getOrCreateInstance(prozor).show();
    }

    window.otvoriUnosStavke = otvori;
    vrsta.addEventListener('change', primijeni);
    predmet.addEventListener('change', function () { osvjeziStadije(''); });
    forma.addEventListener('submit', primijeni);
    document.addEventListener('click', function (event) {
        var gumb = event.target.closest('[data-unos-otvori]');
        if (!gumb) return;
        event.preventDefault();
        otvori({
            kind: gumb.getAttribute('data-kind') || '',
            datum: gumb.getAttribute('data-datum') || '',
            matter: gumb.getAttribute('data-matter') || '',
            stage: gumb.getAttribute('data-stage') || '',
            user: gumb.getAttribute('data-user') || ''
        });
    });
    osvjeziStadije(stadij.getAttribute('data-odabrano') || '');
    primijeni();
    if (prozor.getAttribute('data-otvori') === '1' && window.bootstrap) {
        window.bootstrap.Modal.getOrCreateInstance(prozor).show();
    }
})();
