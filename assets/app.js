/*
 * Allgemeines Verhalten ohne Inline-JavaScript (Voraussetzung für die
 * Content-Security-Policy). Texte kommen über data-Attribute, die der
 * Browser als reinen Text behandelt – kein Einschleusen von Code möglich.
 */
(function () {
    'use strict';

    // Bestätigungsdialog: <form data-bestaetigen="Wirklich löschen?">
    document.addEventListener('submit', function (ereignis) {
        var formular = ereignis.target;
        var frage = formular.getAttribute('data-bestaetigen');
        if (frage && !window.confirm(frage)) {
            ereignis.preventDefault();
            ereignis.stopImmediatePropagation();
        }
    }, true);

    // "Zurück"-Links: Browser-Verlauf nutzen, sonst normaler Link als Rückfall.
    document.querySelectorAll('[data-zurueck]').forEach(function (link) {
        link.addEventListener('click', function (ereignis) {
            if (window.history.length > 1) {
                ereignis.preventDefault();
                window.history.back();
            }
        });
    });

    // Hell/Dunkel-Umschalter
    var schalter = document.getElementById('theme-schalter');
    if (schalter) {
        schalter.addEventListener('click', function () {
            var aktuell = document.documentElement.getAttribute('data-theme');
            if (!aktuell) {
                aktuell = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dunkel' : 'hell';
            }
            var neu = aktuell === 'dunkel' ? 'hell' : 'dunkel';
            document.documentElement.setAttribute('data-theme', neu);
            try { localStorage.setItem('theme', neu); } catch (e) {}
        });
    }

    // Rollenauswahl blendet die Gruppenauswahl für eingeschränkte Benutzer ein/aus:
    // <select data-rolle-umschalter="#id-der-gruppenauswahl">
    document.querySelectorAll('[data-rolle-umschalter]').forEach(function (auswahl) {
        var ziel = document.querySelector(auswahl.getAttribute('data-rolle-umschalter'));
        if (!ziel) return;
        var aktualisieren = function () {
            ziel.hidden = auswahl.value !== 'eingeschraenkt';
        };
        auswahl.addEventListener('change', aktualisieren);
        aktualisieren();
    });

    // Hamburger-Navigation (mobil)
    var hamburger = document.getElementById('nav-hamburger');
    var navInhalt = document.getElementById('nav-inhalt');
    if (hamburger && navInhalt) {
        hamburger.addEventListener('click', function () {
            var offen = navInhalt.classList.toggle('nav-inhalt--offen');
            hamburger.setAttribute('aria-expanded', offen ? 'true' : 'false');
        });
    }

    // Nav-Dropdowns (details.nav-klappe) bei Außenklick schließen
    document.addEventListener('click', function (ereignis) {
        document.querySelectorAll('details.nav-klappe[open]').forEach(function (klappe) {
            if (!klappe.contains(ereignis.target)) {
                klappe.removeAttribute('open');
            }
        });
    });

    // Escape schließt Dropdowns und Hamburger-Nav
    document.addEventListener('keydown', function (ereignis) {
        if (ereignis.key !== 'Escape') { return; }
        document.querySelectorAll('details.nav-klappe[open]').forEach(function (klappe) {
            klappe.removeAttribute('open');
        });
        if (navInhalt && hamburger) {
            navInhalt.classList.remove('nav-inhalt--offen');
            hamburger.setAttribute('aria-expanded', 'false');
        }
    });

    // In die Zwischenablage kopieren: <button type="button" data-kopieren="element-id">
    document.addEventListener('click', function (ereignis) {
        var taste = ereignis.target.closest('[data-kopieren]');
        if (!taste || !navigator.clipboard) { return; }
        var ziel = document.getElementById(taste.getAttribute('data-kopieren') || '');
        var text = ziel ? ziel.textContent.trim() : '';
        if (!text) { return; }
        navigator.clipboard.writeText(text).then(function () {
            var original = taste.textContent;
            taste.textContent = 'Kopiert!';
            setTimeout(function () { taste.textContent = original; }, 1500);
        });
    });

    // Bild-Reihenfolge: Up/Down-Klick im JS abfangen, DOM umsortieren.
    // Non-JS-Fallback: Buttons sind type="submit" und lösen Seiten-POST aus.
    var bildListe = document.getElementById('bild-reihenfolge-liste');
    if (bildListe) {
        bildListe.addEventListener('click', function (ereignis) {
            var taste = ereignis.target.closest('button[name]');
            if (!taste) { return; }
            var nameAttr = taste.getAttribute('name') || '';
            var nachOben = nameAttr.indexOf('bild_verschieben_oben') === 0;
            var nachUnten = nameAttr.indexOf('bild_verschieben_unten') === 0;
            if (!nachOben && !nachUnten) { return; }
            ereignis.preventDefault();
            var karte = taste.closest('[data-bild-id]');
            if (!karte) { return; }
            if (nachOben) {
                var vorher = karte.previousElementSibling;
                if (vorher) { bildListe.insertBefore(karte, vorher); }
            } else {
                var nachher = karte.nextElementSibling;
                if (nachher) { nachher.insertAdjacentElement('afterend', karte); }
            }
            bildListe.querySelectorAll('[data-bild-id]').forEach(function (k) {
                var hidden = k.querySelector('input[name="bild_reihenfolge[]"]');
                if (hidden) { hidden.value = k.getAttribute('data-bild-id'); }
            });
        });
    }
})();
