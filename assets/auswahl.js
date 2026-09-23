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
            return roh ? new Set(JSON.parse(roh)) : new Set();
        } catch (e) {
            return new Set();
        }
    }

    function speichereAuswahl(schluessel, menge) {
        try {
            localStorage.setItem(schluessel, JSON.stringify(Array.from(menge)));
        } catch (e) {}
    }

    function init(root) {
        var schluessel = root.dataset.auswahlSchluessel;
        if (!schluessel) return;

        var startwerte = null;
        try {
            startwerte = JSON.parse(root.dataset.auswahlStart || 'null');
        } catch (e) {}

        if (startwerte !== null && localStorage.getItem(schluessel) === null) {
            speichereAuswahl(schluessel, new Set(startwerte));
        }

        var auswahl = ladeAuswahl(schluessel);
        var zaehler = root.querySelector('[data-auswahl-zaehler]');
        var absenden = root.querySelectorAll('[data-auswahl-formular]');

        function aktualisiereZaehler() {
            if (zaehler) {
                zaehler.textContent = String(auswahl.size);
            }
            root.querySelectorAll('[data-auswahl-leer-hinweis]').forEach(function (el) {
                el.style.display = auswahl.size === 0 ? '' : 'none';
            });
        }

        root.querySelectorAll('input[type=checkbox][data-werk-id]').forEach(function (cb) {
            var id = cb.dataset.werkId;
            cb.checked = auswahl.has(id);
            cb.addEventListener('change', function () {
                if (cb.checked) {
                    auswahl.add(id);
                } else {
                    auswahl.delete(id);
                }
                speichereAuswahl(schluessel, auswahl);
                aktualisiereZaehler();
            });
        });

        absenden.forEach(function (form) {
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
                if (!confirm('Auswahl wirklich leeren?')) return;
                auswahl.clear();
                speichereAuswahl(schluessel, auswahl);
                root.querySelectorAll('input[type=checkbox][data-werk-id]').forEach(function (cb) { cb.checked = false; });
                aktualisiereZaehler();
            });
        });

        aktualisiereZaehler();
    }

    function loescheAuswahl(schluessel) {
        try { localStorage.removeItem(schluessel); } catch (e) {}
    }
    window.PeterAuswahl = { loesche: loescheAuswahl };

    document.querySelectorAll('[data-auswahl-schluessel]').forEach(init);
})();
