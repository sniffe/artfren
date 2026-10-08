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
/** @var array $summen */
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
// Tabellenkopf: "(€)"-Suffix weglassen, da Spaltenbreite begrenzt.
$thLabel = static fn(string $key): string => str_replace(' (€)', '', t($key));
$geldLocale = \App\I18n::aktiv() === 'de' ? 'de-DE' : 'en-US';
?>
<div data-auswahl-schluessel="<?= Helpers::e($auswahlSchluessel) ?>"
     data-geld-locale="<?= Helpers::e($geldLocale) ?>"
     data-auswahl-start="<?= Helpers::e($auswahlStart) ?>"
     data-treffer-ids="<?= Helpers::e(json_encode($trefferIds)) ?>">

<?php if ($gruppe): ?>
    <h2><?= Helpers::e(t('werke.fuer_gruppe', ['name' => $gruppe['name']])) ?></h2>
    <p class="text-sekundaer"><?= Helpers::e(t('werke.gruppe_hinweis')) ?></p>
<?php else: ?>
    <div class="toolbar">
        <h2 style="margin:0;"><?= Helpers::e(t('werke.titel')) ?></h2>
        <a href="/werk_bearbeiten.php" class="btn btn--primaer"><?= Helpers::e(t('werke.neu_anlegen')) ?></a>
    </div>
<?php endif; ?>

<form method="get" action="/werke.php" class="werkstatt-leiste">
    <?php if ($gruppeId !== null): ?><input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>"><?php endif; ?>
    <input type="hidden" name="sort" value="<?= Helpers::e($sortierung) ?>">
    <?php if ($absteigend): ?><input type="hidden" name="richtung" value="ab"><?php endif; ?>
    <div class="feld">
        <label for="q"><?= Helpers::e(t('werke.suche_label')) ?></label>
        <input type="search" id="q" name="q" value="<?= Helpers::e($filter['q']) ?>" placeholder="<?= Helpers::e(t('werke.suche_placeholder')) ?>">
    </div>
    <div class="feld">
        <label for="ort"><?= Helpers::e(t('werke.spalte_ort')) ?></label>
        <select id="ort" name="ort">
            <option value=""><?= Helpers::e(t('werke.alle_orte')) ?></option>
            <?php foreach ($orte as $ort): ?>
                <option value="<?= Helpers::e($ort) ?>" <?= $filter['ort'] === $ort ? 'selected' : '' ?>><?= Helpers::e($ort) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="feld" style="min-width:150px;">
        <label for="maler"><?= Helpers::e(t('feld.maler')) ?></label>
        <select id="maler" name="maler">
            <option value=""><?= Helpers::e(t('werke.alle_kuenstler')) ?></option>
            <?php foreach ($malerListe as $m): ?>
                <option value="<?= Helpers::e($m) ?>" <?= $filter['maler'] === $m ? 'selected' : '' ?>><?= Helpers::e($m) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="feld" style="min-width:150px;">
        <label for="status"><?= Helpers::e(t('feld.status_farbe')) ?></label>
        <select id="status" name="status">
            <option value=""><?= Helpers::e(t('werke.alle_status')) ?></option>
            <option value="ohne" <?= $filter['status'] === 'ohne' ? 'selected' : '' ?>><?= Helpers::e(t('werke.ohne_markierung')) ?></option>
            <?php foreach (Helpers::STATUS_FARBEN as $wert => $label): ?>
                <option value="<?= $wert ?>" <?= $filter['status'] === $wert ? 'selected' : '' ?>><?= Helpers::e(t('status.' . $wert)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="feld" style="min-width:150px;">
        <label for="web_freigabe"><?= Helpers::e(t('werke.web_freigabe_label')) ?></label>
        <select id="web_freigabe" name="web_freigabe">
            <option value=""><?= Helpers::e(t('werke.alle_status')) ?></option>
            <option value="ja" <?= $filter['web_freigabe'] === 'ja' ? 'selected' : '' ?>><?= Helpers::e(t('werke.freigegeben')) ?></option>
            <option value="nein" <?= $filter['web_freigabe'] === 'nein' ? 'selected' : '' ?>><?= Helpers::e(t('werke.nicht_freigegeben')) ?></option>
        </select>
    </div>
    <div class="feld" style="min-width:130px;justify-content:flex-end;">
        <label>&nbsp;</label>
        <label style="display:inline-flex;align-items:center;gap:6px;color:var(--farbe-text);font-size:var(--schrift-groesse-basis);">
            <input type="checkbox" name="ohne_bild" value="1" <?= $filter['ohne_bild'] ? 'checked' : '' ?>>
            <?= Helpers::e(t('werke.ohne_bild')) ?>
        </label>
    </div>
    <button type="submit" class="btn"><?= Helpers::e(t('werke.filtern')) ?></button>
    <?php if ($filterAktiv): ?>
        <a href="<?= Helpers::e($url(['q' => null, 'ort' => null, 'maler' => null, 'status' => null, 'web_freigabe' => null, 'ohne_bild' => null, 'seite' => null])) ?>" class="btn"><?= Helpers::e(t('werke.filter_zuruecksetzen')) ?></a>
    <?php endif; ?>
</form>

<div class="treffer-auswahl text-klein">
    <span class="text-sekundaer"><?= Helpers::e(t('werke.treffer_info', ['n' => $gesamtAnzahl, 'seite' => $seite, 'gesamt' => $seitenAnzahl])) ?></span>
    <?php if ($gesamtAnzahl > 0): ?>
        <button type="button" class="btn btn--klein" data-treffer-auswaehlen><?= Helpers::e(t('werke.alle_auswaehlen', ['n' => $gesamtAnzahl])) ?></button>
        <button type="button" class="btn btn--klein" data-treffer-abwaehlen><?= Helpers::e(t('werke.abwaehlen')) ?></button>
    <?php endif; ?>
</div>

<table class="werkliste">
    <thead>
        <tr>
            <th></th>
            <th><?= Helpers::e(t('werke.spalte_bild')) ?></th>
            <?= $kopf('ort', t('werke.spalte_ort')) ?>
            <?= $kopf('maler', t('feld.maler')) ?>
            <?= $kopf('titel', t('feld.titel')) ?>
            <th><?= Helpers::e(t('feld.format')) ?></th>
            <th><?= Helpers::e(t('feld.technik')) ?></th>
            <?= $kopf('jahr', t('werke.spalte_jahr'), true) ?>
            <?= $kopf('ankaufsjahr', $thLabel('feld.ankaufjahr'), true) ?>
            <?= $kopf('gekauft_von', t('feld.ankauf')) ?>
            <?= $kopf('ankaufswert', $thLabel('feld.ankaufswert'), true) ?>
            <?= $kopf('wert', $thLabel('feld.wert'), true) ?>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($werke as $w):
        $thumb = Helpers::bildUrl($w['bild_id'], $w['bild_dateiname'], 't');
        $zeilenUrl = $url() . '#werk-' . (int) $w['id'];
        $werkText = trim(($w['maler'] ?? '') . ' – ' . ($w['titel'] ?? ''), ' –'); ?>
        <tr id="werk-<?= (int) $w['id'] ?>"
            data-ankaufswert="<?= $w['ankaufswert'] !== null ? Helpers::e((string) $w['ankaufswert']) : '' ?>"
            data-wert="<?= $w['wert'] !== null ? Helpers::e((string) $w['wert']) : '' ?>">
            <td data-label="<?= Helpers::e(t('werke.auswahl_label')) ?>"><input type="checkbox" data-werk-id="<?= (int) $w['id'] ?>" aria-label="<?= Helpers::e(t('werke.auswahl_aria')) ?>"></td>
            <td data-label="<?= Helpers::e(t('werke.spalte_bild')) ?>">
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
                            title="<?= $w['bild_dateiname'] ? Helpers::e(t('werke.bild_fehlt', ['name' => $w['bild_dateiname']])) : Helpers::e(t('werke.bild_hochladen')) ?>"
                            aria-label="<?= Helpers::e(t('werke.bild_hochladen')) ?>">+</button>
                <?php endif; ?>
            </td>
            <td data-label="<?= Helpers::e(t('werke.spalte_ort')) ?>"><?= Helpers::e($w['ort']) ?></td>
            <td data-label="<?= Helpers::e(t('feld.maler')) ?>"><?= Helpers::e($w['maler']) ?></td>
            <td data-label="<?= Helpers::e(t('feld.titel')) ?>"><?= Helpers::e($w['titel']) ?></td>
            <td data-label="<?= Helpers::e(t('feld.format')) ?>"><?= Helpers::e($w['format']) ?></td>
            <td data-label="<?= Helpers::e(t('feld.technik')) ?>"><?= Helpers::e($w['technik']) ?></td>
            <td data-label="<?= Helpers::e(t('werke.spalte_jahr')) ?>" class="num"><?= Helpers::e((string) $w['entstehungsjahr']) ?></td>
            <td data-label="<?= Helpers::e($thLabel('feld.ankaufjahr')) ?>" class="num"><?= Helpers::e((string) ($w['ankaufjahr'] ?? '')) ?></td>
            <td data-label="<?= Helpers::e(t('feld.ankauf')) ?>"><?= Helpers::e((string) ($w['ankauf'] ?? '')) ?></td>
            <td data-label="<?= Helpers::e($thLabel('feld.ankaufswert')) ?>" class="num"><?= Helpers::formatGeld($w['ankaufswert'] !== null ? (float) $w['ankaufswert'] : null) ?></td>
            <td data-label="<?= Helpers::e($thLabel('feld.wert')) ?>" class="num"><?= Helpers::formatGeld($w['wert'] !== null ? (float) $w['wert'] : null) ?></td>
            <td data-label="" style="white-space:nowrap;">
                <?php if ($w['status_farbe']): ?><span class="status-punkt status-punkt--<?= Helpers::e($w['status_farbe']) ?>" title="<?= Helpers::e(Helpers::statusLabel($w['status_farbe'])) ?>"></span><?php endif; ?>
                <a href="/werk.php?id=<?= (int) $w['id'] ?>" class="ikon-link" title="<?= Helpers::e(t('werk.ansehen')) ?>" aria-label="<?= Helpers::e(t('werk.ansehen')) ?>"><?= Helpers::icon('auge') ?></a>
                <a href="/werk_bearbeiten.php?id=<?= (int) $w['id'] ?>&amp;zurueck=<?= rawurlencode($zeilenUrl) ?>" class="ikon-link" title="<?= Helpers::e(t('allg.bearbeiten')) ?>" aria-label="<?= Helpers::e(t('allg.bearbeiten')) ?>"><?= Helpers::icon('stift') ?></a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$werke): ?>
        <tr><td colspan="13" class="text-sekundaer"><?= Helpers::e(t('werke.leer')) ?></td></tr>
    <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="summen-zeile">
            <td colspan="10" class="text-sekundaer text-klein">
                <?= Helpers::e(t('werke.summe_label')) ?>
                · <?= Helpers::e(t('werke.summe_info', ['n' => $gesamtAnzahl, 'm' => (int) ($summen['ohne_wert'] ?? 0)])) ?>
            </td>
            <td class="num text-sekundaer text-klein"><?= Helpers::formatGeld((float) ($summen['ankaufswert'] ?? 0) ?: null) ?></td>
            <td class="num text-sekundaer text-klein"><?= Helpers::formatGeld((float) ($summen['wert'] ?? 0) ?: null) ?></td>
            <td></td>
        </tr>
    </tfoot>
</table>

<div class="seiten-nav">
    <?php if ($seite > 1): ?>
        <a href="<?= Helpers::e($url(['seite' => 1])) ?>" class="btn btn--klein"><?= Helpers::e(t('allg.erste_seite')) ?></a>
        <a href="<?= Helpers::e($url(['seite' => $seite - 1])) ?>" class="btn btn--klein"><?= Helpers::e(t('allg.zurueck_seite')) ?></a>
    <?php endif; ?>
    <span><?= Helpers::e(t('allg.seite_x_von_y', ['x' => $seite, 'y' => $seitenAnzahl])) ?></span>
    <?php if ($seite < $seitenAnzahl): ?>
        <a href="<?= Helpers::e($url(['seite' => $seite + 1])) ?>" class="btn btn--klein"><?= Helpers::e(t('allg.weiter_seite')) ?></a>
        <a href="<?= Helpers::e($url(['seite' => $seitenAnzahl])) ?>" class="btn btn--klein"><?= Helpers::e(t('allg.letzte_seite')) ?></a>
    <?php endif; ?>
</div>

<div class="auswahl-leiste">
    <span>
        <strong data-auswahl-zaehler>0</strong> <?= Helpers::e(t('gruppe_neu.zaehler_suffix')) ?>
        <span class="text-sekundaer" data-auswahl-summen hidden>
            · <?= Helpers::e(t('werke.auswahl_summe_wert', ['betrag' => ''])) ?><span data-auswahl-wert></span>
            · <?= Helpers::e(t('werke.auswahl_summe_ankaufswert', ['betrag' => ''])) ?><span data-auswahl-ankaufswert></span>
        </span>
    </span>
    <div class="toolbar-aktionen">
        <button type="button" class="btn btn--klein" data-auswahl-leeren><?= Helpers::e(t('werke.auswahl_leeren')) ?></button>
        <?php if ($gruppe): ?>
            <form method="post" action="/gruppe.php" data-auswahl-formular>
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="mitglieder_speichern">
                <input type="hidden" name="gruppe_id" value="<?= $gruppeId ?>">
                <button type="submit" class="btn btn--klein btn--primaer"><?= Helpers::e(t('werke.mitglieder_speichern')) ?></button>
            </form>
            <a href="/gruppe.php?id=<?= $gruppeId ?>" class="btn btn--klein"><?= Helpers::e(t('allg.abbrechen')) ?></a>
        <?php else: ?>
            <a href="/gruppe_neu.php" class="btn btn--klein btn--akzent"><?= Helpers::e(t('werke.gruppe_anlegen')) ?></a>
            <form method="post" action="/papierkorb.php" data-auswahl-formular
                  data-bestaetigen="<?= Helpers::e(t('werke.papierkorb_frage')) ?>">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="bulk_papierkorb">
                <button type="submit" class="btn btn--klein btn--gefahr"><?= Helpers::icon('papierkorb') ?> <?= Helpers::e(t('werke.in_papierkorb')) ?></button>
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
        <h3><?= Helpers::e(t('werke.bild_dialog_titel')) ?></h3>
        <p class="text-sekundaer" data-dialog-werk></p>
        <p class="text-klein text-sekundaer" data-dialog-erwartet hidden></p>
        <div class="feld">
            <label for="dialog-bild"><?= Helpers::e(t('werke.bild_upload_label')) ?></label>
            <input type="file" id="dialog-bild" name="bild" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
        </div>
        <div class="feld">
            <label for="dialog-vorhanden"><?= Helpers::e(t('werke.bild_vorhanden_label')) ?></label>
            <input type="text" id="dialog-vorhanden" name="vorhandenes_bild" list="liste-freie-bilder" autocomplete="off"
                   placeholder="<?= $freieBilder ? Helpers::e(t('werke.bild_placeholder_viele', ['n' => count($freieBilder)])) : Helpers::e(t('werke.bild_placeholder_leer')) ?>">
        </div>
        <div class="toolbar-aktionen">
            <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('werke.bild_zuweisen')) ?></button>
            <button type="button" class="btn" data-dialog-schliessen><?= Helpers::e(t('allg.abbrechen')) ?></button>
        </div>
    </form>
</dialog>
<datalist id="liste-freie-bilder"><?php foreach ($freieBilder as $b): ?><option value="<?= Helpers::e($b) ?>"><?php endforeach; ?></datalist>

<script src="<?= \App\Helpers::asset('/assets/auswahl.js') ?>"></script>
<script src="<?= \App\Helpers::asset('/assets/bild-dialog.js') ?>"></script>
