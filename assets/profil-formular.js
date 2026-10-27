/* Profil-Formular: Raster nur beim Kachellayout zeigen; in einem neuen Profil beim
   Wechsel zum Kachellayout die Standardauswahl setzen (Bild, Titelblock, nur Format),
   solange noch keine Feld-Checkbox angefasst wurde. */
(function () {
    'use strict';

    var formular = document.getElementById('profil-formular');
    if (!formular) { return; }
    var layout = document.getElementById('profil-layout');
    var rasterFeld = document.getElementById('profil-kachel-raster-feld');
    var istNeu = formular.dataset.neu === '1';
    var felderBeruehrt = false;

    formular.querySelectorAll('input[name="felder[]"]').forEach(function (cb) {
        cb.addEventListener('change', function () { felderBeruehrt = true; });
    });

    function setze(name, wert) {
        var cb = formular.querySelector('input[name="' + name + '"]');
        if (cb) { cb.checked = wert; }
    }

    if (layout) {
        layout.addEventListener('change', function () {
            if (rasterFeld) { rasterFeld.hidden = layout.value !== 'kachel'; }
            if (istNeu && layout.value === 'kachel' && !felderBeruehrt) {
                formular.querySelectorAll('input[name="felder[]"]').forEach(function (cb) {
                    cb.checked = cb.value === 'format';
                });
                setze('bild', true);
                setze('titelblock', true);
            }
        });
    }
})();
