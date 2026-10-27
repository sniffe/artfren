/* PDF-Dialog: Öffnen, Profil-Auswahl, URL-Bau, Profil speichern */
(function () {
    'use strict';

    var dialog = document.getElementById('pdf-dialog');
    if (!dialog) { return; }

    // ── Dialog öffnen ──────────────────────────────────────────────────────────
    document.querySelectorAll('[data-pdf-dialog]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            aktualisiereLink();
            dialog.showModal();
        });
    });

    // Klick auf Hintergrund oder auf das X schließt den Dialog.
    // (Kein onclick im HTML: Die Sicherheitsrichtlinie der Seite blockiert Inline-Skripte.)
    dialog.addEventListener('click', function (e) {
        if (e.target === dialog || e.target.closest('[data-dialog-schliessen]')) { dialog.close(); }
    });

    // Nach "PDF erstellen" schließen; der Link öffnet das PDF trotzdem.
    var erstellenLink = document.getElementById('pdf-erstellen-link');
    if (erstellenLink) {
        erstellenLink.addEventListener('click', function () {
            setTimeout(function () { dialog.close(); }, 0);
        });
    }

    // Hat der Benutzer in diesem Dialog schon eine Feld-Checkbox angefasst (oder ein
    // Profil gewählt)? Dann ändert der Wechsel zum Kachellayout die Auswahl nicht mehr.
    var felderBeruehrt = false;

    // ── Profil-Auswahl → Felder + Schalter aktualisieren ──────────────────────
    var profilSelect = document.getElementById('pdf-profil');
    if (profilSelect) {
        profilSelect.addEventListener('change', function () {
            var opt = profilSelect.options[profilSelect.selectedIndex];
            if (!opt.value) { return; }
            var felder = (opt.dataset.felder || '').split(',').filter(Boolean);
            dialog.querySelectorAll('input[name="felder[]"]').forEach(function (cb) {
                cb.checked = felder.indexOf(cb.value) !== -1;
            });
            setSwitch('pdf-bild',       opt.dataset.bild       !== '0');
            setSwitch('pdf-titelblock', opt.dataset.titelblock  !== '0');
            setSwitch('pdf-leer',       opt.dataset.leer        !== '0');
            setSwitch('pdf-summe',      opt.dataset.summe       !== '0');
            setSwitch('pdf-deckblatt',  opt.dataset.deckblatt   === '1');
            setSwitch('pdf-fusszeile',  opt.dataset.fusszeile   === '1');
            var layout = document.getElementById('pdf-layout');
            if (layout) { layout.value = opt.dataset.layout || 'einzelblatt'; }
            var raster = document.getElementById('pdf-kachel-raster');
            if (raster) { raster.value = opt.dataset.kachelRaster || '2x2'; }
            felderBeruehrt = true;
            zeigeRaster();
            var updateBtn = document.getElementById('pdf-profil-update');
            if (updateBtn) { updateBtn.hidden = false; }
            aktualisiereLink();
        });
    }

    // Raster-Auswahl nur beim Kachellayout zeigen.
    function zeigeRaster() {
        var feld = document.getElementById('pdf-kachel-raster-feld');
        var layout = document.getElementById('pdf-layout');
        if (feld && layout) { feld.hidden = layout.value !== 'kachel'; }
    }

    dialog.querySelectorAll('input[name="felder[]"]').forEach(function (cb) {
        cb.addEventListener('change', function () { felderBeruehrt = true; });
    });

    // Wechsel zum Kachellayout: Standardauswahl (Bild, Titelblock, nur Format),
    // solange der Benutzer die Felder noch nicht selbst geändert hat.
    var layoutSelect = document.getElementById('pdf-layout');
    if (layoutSelect) {
        layoutSelect.addEventListener('change', function () {
            if (layoutSelect.value === 'kachel' && !felderBeruehrt) {
                dialog.querySelectorAll('input[name="felder[]"]').forEach(function (cb) {
                    cb.checked = cb.value === 'format';
                });
                setSwitch('pdf-bild', true);
                setSwitch('pdf-titelblock', true);
            }
            zeigeRaster();
            aktualisiereLink();
        });
    }

    function setSwitch(id, val) {
        var el = document.getElementById(id);
        if (el) { el.checked = val; }
    }

    // ── Jede Änderung → Link neu bauen ────────────────────────────────────────
    dialog.querySelectorAll('input[name="felder[]"], #pdf-bild, #pdf-titelblock, #pdf-leer, #pdf-summe, #pdf-deckblatt, #pdf-fusszeile, #pdf-kachel-raster').forEach(function (el) {
        el.addEventListener('change', aktualisiereLink);
    });

    function baueUrl() {
        var params = new URLSearchParams();
        params.set('id', dialog.dataset.gruppeId);
        var profilId = profilSelect ? profilSelect.value : '';
        if (profilId) { params.set('profil_id', profilId); }
        params.set('bild',            el('pdf-bild')       ? (el('pdf-bild').checked       ? '1' : '0') : '1');
        params.set('titelblock',      el('pdf-titelblock') ? (el('pdf-titelblock').checked  ? '1' : '0') : '1');
        params.set('leer_ausblenden', el('pdf-leer')       ? (el('pdf-leer').checked        ? '1' : '0') : '1');
        params.set('summe',           el('pdf-summe')      ? (el('pdf-summe').checked       ? '1' : '0') : '1');
        params.set('layout',          el('pdf-layout')     ? (el('pdf-layout').value || 'einzelblatt') : 'einzelblatt');
        params.set('kachel_raster',   el('pdf-kachel-raster') ? (el('pdf-kachel-raster').value || '2x2') : '2x2');
        params.set('deckblatt',       el('pdf-deckblatt')  && el('pdf-deckblatt').checked  ? '1' : '0');
        params.set('fusszeile',       el('pdf-fusszeile')  && el('pdf-fusszeile').checked  ? '1' : '0');
        // felder[] mit wörtlichen Klammern – PHP parst %5B%5D NICHT als Array
        var felderParts = [];
        dialog.querySelectorAll('input[name="felder[]"]:checked').forEach(function (cb) {
            felderParts.push('felder[]=' + encodeURIComponent(cb.value));
        });
        var basis = '/export_gruppe_pdf.php?' + params.toString();
        return felderParts.length > 0 ? basis + '&' + felderParts.join('&') : basis;
    }

    function el(id) { return document.getElementById(id); }

    function aktualisiereLink() {
        var link = document.getElementById('pdf-erstellen-link');
        if (link) { link.href = baueUrl(); }
    }

    // ── Profil speichern ───────────────────────────────────────────────────────
    var alsProfilBtn = document.getElementById('pdf-als-profil');
    if (alsProfilBtn) {
        alsProfilBtn.addEventListener('click', function () {
            var name = prompt(dialog.dataset.profilNameFrage || '');
            if (!name || !name.trim()) { return; }
            speichernProfil(name.trim(), null);
        });
    }

    var profilUpdateBtn = document.getElementById('pdf-profil-update');
    if (profilUpdateBtn) {
        profilUpdateBtn.addEventListener('click', function () {
            if (!profilSelect || !profilSelect.value) { return; }
            var opt = profilSelect.options[profilSelect.selectedIndex];
            // Klammerzusatz "(Standard)" am Ende entfernen
            var name = opt.text.replace(/\s*\(.*\)\s*$/, '').trim();
            speichernProfil(name, parseInt(profilSelect.value, 10));
        });
    }

    function speichernProfil(name, id) {
        var body = new FormData();
        body.set('csrf_token', dialog.dataset.csrf);
        body.set('aktion', 'speichern');
        if (id) { body.set('id', String(id)); }
        // Standard, Freigabe und Sprache bleiben beim Aktualisieren erhalten (siehe export_profile.php).
        body.set('aus_dialog', '1');
        body.set('name', name);
        dialog.querySelectorAll('input[name="felder[]"]:checked').forEach(function (cb) {
            body.append('felder[]', cb.value);
        });
        body.set('bild',            el('pdf-bild')       && el('pdf-bild').checked       ? '1' : '');
        body.set('titelblock',      el('pdf-titelblock') && el('pdf-titelblock').checked  ? '1' : '');
        body.set('leer_ausblenden', el('pdf-leer')       && el('pdf-leer').checked        ? '1' : '');
        body.set('summe',           el('pdf-summe')      && el('pdf-summe').checked       ? '1' : '');
        body.set('layout',          el('pdf-layout') ? (el('pdf-layout').value || 'einzelblatt') : 'einzelblatt');
        body.set('kachel_raster',   el('pdf-kachel-raster') ? (el('pdf-kachel-raster').value || '2x2') : '2x2');
        body.set('deckblatt',       el('pdf-deckblatt')  && el('pdf-deckblatt').checked  ? '1' : '');
        body.set('fusszeile',       el('pdf-fusszeile')  && el('pdf-fusszeile').checked  ? '1' : '');
        fetch('/export_profile.php', { method: 'POST', body: body })
            .then(function () { window.location.reload(); });
    }
})();
