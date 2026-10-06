/*
 * Auswahl-Mechanik für die Kunstwerk-Checkbox-Liste (Werkstatt-Ansicht).
 * Hält die Auswahl in localStorage fest, damit sie über Filter, Seiten
 * und Kontextwechsel (neue Gruppe / Mitglieder bearbeiten) erhalten
 * bleibt – nicht nur für die aktuell sichtbaren Zeilen.
 */
(function () {
    'use strict';

    function ladeAuswahl(schluessel) {
        try {
            var roh = localStorage.getItem(schluessel);
            return roh ? new Set(JSON.parse(roh).map(String)) : new Set();
        } catch (e) {
            return new Set();
        }
    }

    function speichereAuswahl(schluessel, menge) {
        try {
            localStorage.setItem(schluessel, JSON.stringify(Array.from(menge)));
        } catch (e) {}
    }

    function jsonAttribut(element, name) {
        try {
            return JSON.parse(element.getAttribute(name) || 'null');
        } catch (e) {
            return null;
        }
    }

    function init(root) {
        var schluessel = root.getAttribute('data-auswahl-schluessel');
        if (!schluessel) return;

        var startwerte = jsonAttribut(root, 'data-auswahl-start');
        if (startwerte !== null && localStorage.getItem(schluessel) === null) {
            speichereAuswahl(schluessel, new Set(startwerte.map(String)));
        }

        var auswahl = ladeAuswahl(schluessel);
        var checkboxen = root.querySelectorAll('input[type=checkbox][data-werk-id]');

        function aktualisiere() {
            root.querySelectorAll('[data-auswahl-zaehler]').forEach(function (el) {
                el.textContent = String(auswahl.size);
            });
            root.querySelectorAll('[data-auswahl-leer-hinweis]').forEach(function (el) {
                el.hidden = auswahl.size !== 0;
            });
            checkboxen.forEach(function (cb) {
                cb.checked = auswahl.has(cb.getAttribute('data-werk-id'));
            });
            // Geldsummen der Auswahl berechnen und anzeigen.
            var locale = root.getAttribute('data-geld-locale') || 'de-DE';
            var ankaufswertSumme = 0, wertSumme = 0;
            checkboxen.forEach(function (cb) {
                if (!auswahl.has(cb.getAttribute('data-werk-id'))) return;
                var tr = cb.closest('tr');
                if (!tr) return;
                var a = parseFloat(tr.getAttribute('data-ankaufswert') || '');
                var w = parseFloat(tr.getAttribute('data-wert') || '');
                if (!isNaN(a)) ankaufswertSumme += a;
                if (!isNaN(w)) wertSumme += w;
            });
            var fmt = new Intl.NumberFormat(locale, {style: 'currency', currency: 'EUR'});
            root.querySelectorAll('[data-auswahl-wert]').forEach(function (el) {
                el.textContent = fmt.format(wertSumme);
            });
            root.querySelectorAll('[data-auswahl-ankaufswert]').forEach(function (el) {
                el.textContent = fmt.format(ankaufswertSumme);
            });
            root.querySelectorAll('[data-auswahl-summen]').forEach(function (el) {
                el.hidden = auswahl.size === 0;
            });
        }

        function speichern() {
            speichereAuswahl(schluessel, auswahl);
            aktualisiere();
        }

        checkboxen.forEach(function (cb) {
            cb.addEventListener('change', function () {
                var id = cb.getAttribute('data-werk-id');
                if (cb.checked) {
                    auswahl.add(id);
                } else {
                    auswahl.delete(id);
                }
                speichern();
            });
        });

        // Alle Treffer des aktuellen Filters (über alle Seiten) an-/abwählen.
        var treffer = (jsonAttribut(root, 'data-treffer-ids') || []).map(String);
        root.querySelectorAll('[data-treffer-auswaehlen]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                treffer.forEach(function (id) { auswahl.add(id); });
                speichern();
            });
        });
        root.querySelectorAll('[data-treffer-abwaehlen]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                treffer.forEach(function (id) { auswahl.delete(id); });
                speichern();
            });
        });

        root.querySelectorAll('[data-auswahl-formular]').forEach(function (form) {
            form.addEventListener('submit', function () {
                form.querySelectorAll('input[name="werk_ids[]"]').forEach(function (el) { el.remove(); });
                auswahl.forEach(function (id) {
                    var eingabe = document.createElement('input');
                    eingabe.type = 'hidden';
                    eingabe.name = 'werk_ids[]';
                    eingabe.value = id;
                    form.appendChild(eingabe);
                });
            });
        });

        root.querySelectorAll('[data-auswahl-leeren]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!window.confirm('Auswahl wirklich leeren?')) return;
                auswahl.clear();
                speichern();
            });
        });

        aktualisiere();
    }

    // Nach dem Speichern einer Gruppe die zugehörige Auswahl verwerfen:
    // <div data-auswahl-loeschen="auswahl_neu auswahl_gruppe_5">
    document.querySelectorAll('[data-auswahl-loeschen]').forEach(function (el) {
        el.getAttribute('data-auswahl-loeschen').split(/\s+/).forEach(function (schluessel) {
            if (!schluessel) return;
            try { localStorage.removeItem(schluessel); } catch (e) {}
        });
    });

    document.querySelectorAll('[data-auswahl-schluessel]').forEach(init);
})();
