/*
 * Klick auf einen Bild-Platzhalter in der Werkliste öffnet einen Dialog zum
 * Hochladen oder Zuweisen eines vorhandenen Bildes.
 */
(function () {
    'use strict';

    var dialog = document.getElementById('bild-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    var formular = dialog.querySelector('form');

    document.querySelectorAll('[data-bild-zuweisen]').forEach(function (knopf) {
        knopf.addEventListener('click', function () {
            formular.reset();
            formular.elements.werk_id.value = knopf.getAttribute('data-bild-zuweisen');
            formular.elements.zurueck.value = knopf.getAttribute('data-ruecksprung');
            dialog.querySelector('[data-dialog-werk]').textContent = knopf.getAttribute('data-werk-text');

            var erwartet = knopf.getAttribute('data-erwartet');
            var hinweis = dialog.querySelector('[data-dialog-erwartet]');
            hinweis.hidden = !erwartet;
            hinweis.textContent = erwartet ? 'Laut Tabelle erwartet: „' + erwartet + '“ – diese Datei fehlt im Bilder-Ordner.' : '';

            dialog.showModal();
        });
    });

    dialog.querySelector('[data-dialog-schliessen]').addEventListener('click', function () {
        dialog.close();
    });

    // Doppeltes Absenden (z. B. bei langsamem Upload) verhindern.
    formular.addEventListener('submit', function () {
        formular.querySelector('button[type=submit]').disabled = true;
    });
})();
