/*
 * Galerie-Navigation für die öffentliche Detailseite.
 * Wechselt das Hauptbild beim Klick auf einen Thumbnail-Button.
 * Tastatur: Pfeiltasten wechseln Bild (links/rechts) oder Werk (wenn kein Thumbnail).
 */
(function () {
    'use strict';

    var hauptbildImg  = document.getElementById('web-hauptbild-img');
    var streifen      = document.getElementById('web-galerie-streifen');
    var prevLink      = document.getElementById('prev-link');
    var nextLink      = document.getElementById('next-link');

    // Thumbnail-Wechsel
    if (streifen && hauptbildImg) {
        streifen.addEventListener('click', function (ereignis) {
            var taste = ereignis.target.closest('button.web-thumb');
            if (!taste) { return; }
            var url = taste.getAttribute('data-bild-url');
            var alt = taste.getAttribute('data-bild-alt');
            if (url) {
                hauptbildImg.src = url;
                hauptbildImg.alt = alt || '';
            }
            streifen.querySelectorAll('.web-thumb').forEach(function (b) {
                b.classList.remove('web-thumb--aktiv');
            });
            taste.classList.add('web-thumb--aktiv');
        });
    }

    // Tastatur-Navigation
    document.addEventListener('keydown', function (ereignis) {
        if (ereignis.target.tagName === 'INPUT' || ereignis.target.tagName === 'TEXTAREA') { return; }
        if (ereignis.key === 'ArrowLeft' && prevLink) {
            prevLink.click();
        } else if (ereignis.key === 'ArrowRight' && nextLink) {
            nextLink.click();
        }
    });
}());
