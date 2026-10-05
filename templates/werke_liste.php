<?php
/** @var array $werke */
/** @var int[] $trefferIds */
/** @var string[] $orte */
/** @var string[] $malerListe */
/** @var array{q: string, ort: string, maler: string, status: string, web_freigabe: string, ohne_bild: string} $filter */
/** @var string $sortierung */
/** @var bool $absteigend */
/** @var int $seite */
/** @var int $seitenAnzahl */
/** @var int $gesamtAnzahl */
/** @var array|null $gruppe */
/** @var int[] $aktuelleMitglieder */
/** @var string[] $freieBilder */
declare(strict_types=1);

use App\Helpers;

$gruppeId = $gruppe !== null ? (int) $gruppe['id'] : null;
$auswahlSchluessel = $gruppeId !== null ? 'auswahl_gruppe_' . $gruppeId : 'auswahl_neu';
$auswahlStart = $gruppeId !== null ? json_encode($aktuelleMitglieder) : 'null';

$url = static function (array $ueberschreiben = []): string {
    $params = array_merge($_GET, $ueberschreiben);
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return '/werke.php?' . http_build_query($params);
};

$kopf = static function (string $feld, string $text, bool $zahl = false) use ($url, $sortierung, $absteigend): string {
    $aktiv = $sortierung === $feld;
    $richtung = $aktiv && !$absteigend ? 'ab' : null;
    $pfeil = $aktiv ? ($absteigend ? ' ▼' : ' ▲') : '';
    return '<th' . ($zahl ? ' class="num"' : '') . '><a class="' . ($aktiv ? 'sortiert' : '') . '" href="'
        . Helpers::e($url(['sort' => $feld, 'richtung' => $richtung, 'seite' => null])) . '">'
        . Helpers::e($text) . $pfeil . '</a></th>';
};
$filterAktiv = $filter['q'] !== '' || $filter['ort'] !== '' || $filter['maler'] !== ''
    || $filter['status'] !== '' || $filter['web_freigabe'] !== '' || $filter['ohne_bild'] !== '';
?>
<div data-auswahl-schluessel="<?= Helpers::e($auswahlSchluessel) ?>"
     data-auswahl-start="<?= Helpers::e($auswahlStart) ?>"
     data-treffer-ids="<?= Helpers::e(json_encode($trefferIds)) ?>">

<?php if ($gruppe): ?>
    <h2>Werke für Gruppe „<?= Helpers::e($gruppe['name']) ?>“ auswählen</h2>
    <p class="text-sekundaer">Aktuelle Mitglieder sind bereits angehakt. Auswahl anpassen und unten speichern.</p>
<?php else: ?>
    <div class="toolbar">
        <h2 style="margin:0;">Werke</h2>
        <a href="/werk_bearbeiten.php" class="btn btn--primaer">+ Neues Werk anlegen</a>
    </div>
<?php endif; ?>

<form method="get" action="/werke.php" class="werkstatt-leiste">
    <?php if ($gruppeId !== null): ?><input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>"><?php endif; ?>
    <input type="hidden" name="sort" value="<?= Helpers::e($sortierung) ?>">
    <?php if ($absteigend): ?><input type="hidden" name="richtung" value="ab"><?php endif; ?>
    <div class="feld">
        <label for="q">Suche (Maler, Titel, Ort, Technik)</label>
        <input type="search" id="q" name="q" value="<?= Helpers::e($filter['q']) ?>" placeholder="z. B. Zens, Madeira, öl …">
    </div>
    <div class="feld">
        <label for="ort">Ort</label>
        <select id="ort" name="ort">
            <option value="">alle Orte</option>
            <?php foreach ($orte as $ort): ?>
                <option value="<?= Helpers::e($ort) ?>" <?= $filter['ort'] === $ort ? 'selected' : '' ?>><?= Helpers::e($ort) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="feld" style="min-width:150px;">
        <label for="maler">Maler</label>
        <select id="maler" name="maler">
            <option value="">alle Maler</option>
            <?php foreach ($malerListe as $m): ?>
                <option value="<?= Helpers::e($m) ?>" <?= $filter['maler'] === $m ? 'selected' : '' ?>><?= Helpers::e($m) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="feld" style="min-width:150px;">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">alle</option>
            <option value="ohne" <?= $filter['status'] === 'ohne' ? 'selected' : '' ?>>ohne Markierung</option>
            <?php foreach (Helpers::STATUS_FARBEN as $wert => $label): ?>
                <option value="<?= $wert ?>" <?= $filter['status'] === $wert ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="feld" style="min-width:150px;">
        <label for="web_freigabe">Web-Freigabe</label>
        <select id="web_freigabe" name="web_freigabe">
            <option value="">alle</option>
            <option value="ja" <?= $filter['web_freigabe'] === 'ja' ? 'selected' : '' ?>>freigegeben</option>
            <option value="nein" <?= $filter['web_freigabe'] === 'nein' ? 'selected' : '' ?>>nicht freigegeben</option>
        </select>
    </div>
    <div class="feld" style="min-width:130px;justify-content:flex-end;">
        <label>&nbsp;</label>
        <label style="display:inline-flex;align-items:center;gap:6px;color:var(--farbe-text);font-size:var(--schrift-groesse-basis);">
            <input type="checkbox" name="ohne_bild" value="1" <?= $filter['ohne_bild'] ? 'checked' : '' ?>>
            Ohne Bild
        </label>
    </div>
    <button type="submit" class="btn">Filtern</button>
    <?php if ($filterAktiv): ?>
        <a href="<?= Helpers::e($url(['q' => null, 'ort' => null, 'maler' => null, 'status' => null, 'web_freigabe' => null, 'ohne_bild' => null, 'seite' => null])) ?>" class="btn">Filter zurücksetzen</a>
    <?php endif; ?>
</form>

<div class="treffer-auswahl text-klein">
    <span class="text-sekundaer"><?= $gesamtAnzahl ?> Werke gefunden · Seite <?= $seite ?> von <?= $seitenAnzahl ?></span>
    <?php if ($gesamtAnzahl > 0): ?>
        <button type="button" class="btn btn--klein" data-treffer-auswaehlen>Alle <?= $gesamtAnzahl ?> Treffer auswählen</button>
        <button type="button" class="btn btn--klein" data-treffer-abwaehlen>Treffer abwählen</button>
    <?php endif; ?>
</div>

<table class="werkliste">
    <thead>
        <tr>
            <th></th>
            <th>Bild</th>
            <?= $kopf('ort', 'Ort') ?>
            <?= $kopf('maler', 'Maler') ?>
            <?= $kopf('titel', 'Titel') ?>
            <th>Format</th>
            <th>Technik</th>
            <?= $kopf('jahr', 'Jahr', true) ?>
            <?= $kopf('wert', 'Wert', true) ?>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($werke as $w):
        $thumb = Helpers::bildUrl($w['bild_id'], $w['bild_dateiname'], 't');
        $zeilenUrl = $url() . '#werk-' . (int) $w['id'];
        $werkText = trim(($w['maler'] ?? '') . ' – ' . ($w['titel'] ?? ''), ' –'); ?>
        <tr id="werk-<?= (int) $w['id'] ?>">
            <td data-label="Auswahl"><input type="checkbox" data-werk-id="<?= (int) $w['id'] ?>" aria-label="Auswählen"></td>
            <td data-label="Bild">
                <?php if ($thumb): ?>
                    <div class="thumb-wrapper">
                        <img class="thumb" src="<?= Helpers::e($thumb) ?>" alt="" loading="lazy">
                        <?php if (($w['bild_anzahl'] ?? 1) > 1): ?>
                            <span class="bild-anzahl-badge">+<?= (int) $w['bild_anzahl'] - 1 ?></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <button type="button" class="platzhalter-thumb platzhalter-thumb--aktiv"
                            data-bild-zuweisen="<?= (int) $w['id'] ?>"
                            data-werk-text="<?= Helpers::e($werkText) ?>"
                            data-erwartet="<?= Helpers::e((string) $w['bild_dateiname']) ?>"
                            data-ruecksprung="<?= Helpers::e($zeilenUrl) ?>"
                            title="<?= $w['bild_dateiname'] ? 'Bilddatei „' . Helpers::e($w['bild_dateiname']) . '“ fehlt – klicken zum Hochladen' : 'Bild hochladen oder zuweisen' ?>"
                            aria-label="Bild hochladen oder zuweisen">+</button>
                <?php endif; ?>
            </td>
            <td data-label="Ort"><?= Helpers::e($w['ort']) ?></td>
            <td data-label="Maler"><?= Helpers::e($w['maler']) ?></td>
            <td data-label="Titel"><?= Helpers::e($w['titel']) ?></td>
            <td data-label="Format"><?= Helpers::e($w['format']) ?></td>
            <td data-label="Technik"><?= Helpers::e($w['technik']) ?></td>
            <td data-label="Jahr" class="num"><?= Helpers::e((string) $w['entstehungsjahr']) ?></td>
            <td data-label="Wert" class="num"><?= Helpers::formatGeld($w['wert'] !== null ? (float) $w['wert'] : null) ?></td>
            <td data-label="" style="white-space:nowrap;">
                <?php if ($w['status_farbe']): ?><span class="status-punkt status-punkt--<?= Helpers::e($w['status_farbe']) ?>" title="<?= Helpers::e(Helpers::statusLabel($w['status_farbe'])) ?>"></span><?php endif; ?>
                <a href="/werk.php?id=<?= (int) $w['id'] ?>" class="ikon-link" title="Ansehen" aria-label="Werk ansehen"><?= Helpers::icon('auge') ?></a>
                <a href="/werk_bearbeiten.php?id=<?= (int) $w['id'] ?>&amp;zurueck=<?= rawurlencode($zeilenUrl) ?>" class="ikon-link" title="Bearbeiten" aria-label="Werk bearbeiten"><?= Helpers::icon('stift') ?></a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$werke): ?>
        <tr><td colspan="10" class="text-sekundaer">Keine Werke gefunden.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<div class="seiten-nav">
    <?php if ($seite > 1): ?><a href="<?= Helpers::e($url(['seite' => $seite - 1])) ?>" class="btn btn--klein">← zurück</a><?php endif; ?>
    <span>Seite <?= $seite ?> / <?= $seitenAnzahl ?></span>
    <?php if ($seite < $seitenAnzahl): ?><a href="<?= Helpers::e($url(['seite' => $seite + 1])) ?>" class="btn btn--klein">weiter →</a><?php endif; ?>
</div>

<div class="auswahl-leiste">
    <span><strong data-auswahl-zaehler>0</strong> Werke ausgewählt</span>
    <div class="toolbar-aktionen">
        <button type="button" class="btn" data-auswahl-leeren>Auswahl leeren</button>
        <?php if ($gruppe): ?>
            <form method="post" action="/gruppe.php" data-auswahl-formular>
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="mitglieder_speichern">
                <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
                <button type="submit" class="btn btn--primaer">Auswahl als Mitglieder speichern</button>
            </form>
            <a href="/gruppe.php?id=<?= $gruppeId ?>" class="btn">Abbrechen</a>
        <?php else: ?>
            <a href="/gruppe_neu.php" class="btn btn--primaer">Weiter: Gruppe anlegen</a>
            <form method="post" action="/papierkorb.php" data-auswahl-formular
                  data-bestaetigen="Alle ausgewählten Werke in den Papierkorb verschieben?">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="bulk_papierkorb">
                <button type="submit" class="btn btn--gefahr"><?= Helpers::icon('papierkorb') ?> In den Papierkorb</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</div>

<dialog id="bild-dialog" class="dialog">
    <form method="post" action="/werk_bild.php" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="werk_id" value="">
        <input type="hidden" name="zurueck" value="">
        <h3>Bild zuweisen</h3>
        <p class="text-sekundaer" data-dialog-werk></p>
        <p class="text-klein text-sekundaer" data-dialog-erwartet hidden></p>
        <div class="feld">
            <label for="dialog-bild">Bild hochladen</label>
            <input type="file" id="dialog-bild" name="bild" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
        </div>
        <div class="feld">
            <label for="dialog-vorhanden">… oder vorhandenes Bild aus dem Bilder-Ordner</label>
            <input type="text" id="dialog-vorhanden" name="vorhandenes_bild" list="liste-freie-bilder" autocomplete="off"
                   placeholder="<?= $freieBilder ? count($freieBilder) . ' noch nicht zugeordnete Bilder – Namen tippen' : 'Dateiname' ?>">
        </div>
        <div class="toolbar-aktionen">
            <button type="submit" class="btn btn--primaer">Zuweisen</button>
            <button type="button" class="btn" data-dialog-schliessen>Abbrechen</button>
        </div>
    </form>
</dialog>
<datalist id="liste-freie-bilder"><?php foreach ($freieBilder as $b): ?><option value="<?= Helpers::e($b) ?>"><?php endforeach; ?></datalist>

<script src="/assets/auswahl.js"></script>
<script src="/assets/bild-dialog.js"></script>
