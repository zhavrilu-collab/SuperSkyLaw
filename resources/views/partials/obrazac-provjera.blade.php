<style>
    .obavezno { color: var(--sustav-greska, #e31e24); font-weight: 700; }
    .polje-greska { color: var(--sustav-greska, #e31e24); font-size: 12px; margin-top: 4px; }
</style>
<script data-obrazac-provjera>
(function () {
    function nazivPolja(el) {
        var label = el.id ? document.querySelector('label[for="' + CSS.escape(el.id) + '"]') : null;
        if (!label) {
            var box = el.closest('.mb-1, .mb-2, .mb-3, .col, .form-check') || el.parentElement;
            label = box ? box.querySelector('label') : null;
        }
        if (!label) return '';
        return label.textContent.replace(/\*/g, '').replace(/\s+/g, ' ').trim();
    }

    function mala(tekst) {
        return tekst.charAt(0).toLocaleLowerCase('hr') + tekst.slice(1);
    }

    function poruka(el) {
        var naziv = nazivPolja(el);
        if (el.validity.valueMissing) {
            if (el.type === 'checkbox' || el.type === 'radio') return 'Ovo treba potvrditi.';
            if (el.type === 'file') return 'Odaberite datoteku.';
            if (el.tagName === 'SELECT') return naziv ? 'Odaberite ' + mala(naziv) + '.' : 'Odaberite stavku.';
            return naziv ? 'Unesite ' + mala(naziv) + '.' : 'Ovo polje je obavezno.';
        }
        if (el.validity.typeMismatch) {
            if (el.type === 'email') return 'Unesite ispravnu e-mail adresu.';
            if (el.type === 'url') return 'Unesite ispravnu poveznicu.';
            return 'Vrijednost nije ispravna.';
        }
        if (el.validity.tooShort) return 'Unos je prekratak.';
        if (el.validity.tooLong) return 'Unos je predugačak.';
        if (el.validity.rangeUnderflow || el.validity.rangeOverflow || el.validity.stepMismatch) return 'Unesite ispravan broj.';
        if (el.validity.patternMismatch) return 'Vrijednost nije u traženom obliku.';
        return 'Provjerite ovo polje.';
    }

    function oznaciObavezna(korijen) {
        (korijen || document).querySelectorAll('input[required], select[required], textarea[required]').forEach(function (el) {
            if (el.type === 'hidden') return;
            var label = el.id ? document.querySelector('label[for="' + CSS.escape(el.id) + '"]') : null;
            if (!label) {
                var box = el.closest('.mb-1, .mb-2, .mb-3, .col, .form-check') || el.parentElement;
                label = box ? box.querySelector('label') : null;
            }
            if (!label || label.querySelector('.obavezno')) return;
            var zvjezdica = document.createElement('span');
            zvjezdica.className = 'obavezno';
            zvjezdica.setAttribute('aria-hidden', 'true');
            zvjezdica.textContent = ' *';
            label.appendChild(zvjezdica);
        });
    }

    function ocisti(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        form.querySelectorAll('.polje-greska').forEach(function (el) { el.remove(); });
    }

    function pokazi(el) {
        el.classList.add('is-invalid');
        var porukaEl = document.createElement('div');
        porukaEl.className = 'invalid-feedback d-block polje-greska';
        porukaEl.textContent = poruka(el);
        el.insertAdjacentElement('afterend', porukaEl);
    }

    function provjeri(form) {
        ocisti(form);
        var prvo = null;
        Array.prototype.forEach.call(form.elements, function (el) {
            if (!el.willValidate || el.disabled || el.type === 'hidden') return;
            if (el.checkValidity()) return;
            if (!prvo) prvo = el;
            if (el.type === 'radio' && form.querySelector('.polje-greska[data-ime="' + el.name + '"]')) return;
            pokazi(el);
            if (el.type === 'radio') {
                var zadnja = form.querySelectorAll('.polje-greska');
                zadnja[zadnja.length - 1].setAttribute('data-ime', el.name);
            }
        });
        if (prvo) prvo.focus();
        return !prvo;
    }

    document.querySelectorAll('form').forEach(function (form) { form.noValidate = true; });
    oznaciObavezna(document);
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.bezProvjere === '1') return;
        form.noValidate = true;
        if (provjeri(form)) return;
        event.preventDefault();
        event.stopPropagation();
    }, true);

    document.querySelectorAll('[data-flash-toast]').forEach(function (toast) {
        window.setTimeout(function () {
            toast.classList.add('flash-toast-hide');
            window.setTimeout(function () { toast.remove(); }, 320);
        }, toast.classList.contains('alert-danger') ? 7000 : 4000);
    });
})();
</script>
