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
})();
