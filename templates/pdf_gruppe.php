<?php
/** @var array $gruppe */
/** @var array $werke   jeweils mit 'bild_data_uri' */
/** @var array $pdfFelder   Felddefinitionen aus Registry, bereits gefiltert */
/** @var bool $zeigeBild */
/** @var bool $zeigeTitelblock */
/** @var bool $leerAusblenden */
/** @var bool $zeigeSumme */
/** @var string $layout  'einzelblatt'|'liste' */
declare(strict_types=1);

use App\Helpers;
use App\I18n;

$tabellenFelder = array_values(array_filter($pdfFelder, static fn(array $f): bool => $f['typ'] !== 'langtext'));
$langtextFelder = array_values(array_filter($pdfFelder, static fn(array $f): bool => $f['typ'] === 'langtext'));
$geldFelder     = array_values(array_filter($pdfFelder, static fn(array $f): bool => $f['typ'] === 'geld'));
?>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; color: #1C1B1A; font-size: 11pt; }
    /* ── Einzelblatt ── */
    .seite { page-break-after: always; padding-top: 20px; }
    .seite:last-child { page-break-after: auto; }
    .kopf { font-size: 9pt; color: #6B6862; border-bottom: 1px solid #E2E0DC; padding-bottom: 6px; }
    .bild-rahmen { text-align: center; border: 1px solid #E2E0DC; padding: 14px; margin-top: 16px; }
    .bild-rahmen img { max-width: 460px; max-height: 360px; }
    .kein-bild { color: #6B6862; padding: 80px 0; }
    .etikett { text-align: center; margin-top: 16px; }
    .etikett .maler { font-variant: small-caps; letter-spacing: 1px; font-size: 12pt; }
    .etikett .titel { font-style: italic; font-size: 16pt; margin: 4px 0; }
    .etikett .meta { font-size: 10pt; color: #6B6862; }
    table.daten { width: 100%; margin-top: 24px; border-collapse: collapse; }
    table.daten td { padding: 4px 8px; border-bottom: 1px solid #E2E0DC; font-size: 10pt; }
    table.daten td.label { color: #6B6862; width: 160px; }
    .beschreibung { padding: 4px 8px; font-size: 10pt; line-height: 1.4; }
    .beschreibung-label { padding: 10px 8px 2px; font-size: 9pt; color: #6B6862; }
    /* ── Liste ── */
    .liste-kopf { font-size: 10pt; margin-bottom: 16px; }
    .liste-kopf h1 { font-size: 14pt; color: #1C1B1A; margin: 0 0 4px; }
    .liste-meta { font-size: 9pt; color: #6B6862; }
    /* Feste Spaltenbreiten (in der Kopfzeile) und Umbruch langer Wörter: Die Tabelle
       bleibt immer innerhalb der Seite, egal wie viele Felder gewählt sind. */
    table.werkliste { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.werkliste th { background: #f0ede8; padding: 4px 4px; text-align: left; vertical-align: bottom; border-bottom: 2px solid #C8C4BC; font-size: 0.92em; }
    table.werkliste td { padding: 4px 4px; border-bottom: 1px solid #E2E0DC; vertical-align: top; }
    table.werkliste th, table.werkliste td { word-wrap: break-word; overflow-wrap: break-word; }
    table.werkliste tr.summe-zeile td { border-top: 2px solid #C8C4BC; border-bottom: none; font-weight: bold; }
    .num { text-align: right; }
    table.werkliste td.num { white-space: nowrap; }
</style>
</head>
<body>
<?php if ($layout === 'liste'): ?>
<?php
// ── Listen-Layout ─────────────────────────────────────────────────────────────
$summen     = array_fill_keys(array_column($geldFelder, 'key'), 0.0);
$ohneAngabe = array_fill_keys(array_column($geldFelder, 'key'), 0);
$zeilenZahl = 0;
?>
<div class="liste-kopf">
    <h1><?= Helpers::e($gruppe['name']) ?></h1>
    <div class="liste-meta">
        <?= Helpers::e(t('pdf.liste_datum')) ?> <?= date('d.m.Y') ?>
        · <?= Helpers::e(I18n::plural(count($werke), 'pdf.liste_werke', ['n' => count($werke)])) ?>
    </div>
</div>
<?php
// Spaltenbreiten nach Inhalt gewichten, Schriftgröße nach Spaltenzahl.
$gewicht = static function (array $f): float {
    if ($f['typ'] === 'langtext') { return 4.0; }
    if ($f['typ'] === 'geld') { return 1.9; }
    return match ($f['key']) {
        'titel' => 3.0,
        'maler', 'technik' => 2.2,
        'ort', 'herkunft', 'ankauf', 'copyright' => 2.0,
        'entstehungsjahr', 'ankaufjahr' => 2.1,
        'werktyp', 'web_freigabe', 'status_farbe' => 1.8,
        default => 1.5,
    };
};
$gewichte = array_map($gewicht, $pdfFelder);
$summeGewichte = 0.6 + array_sum($gewichte);
$spalten = count($pdfFelder);
$schrift = $spalten <= 6 ? '9pt' : ($spalten <= 9 ? '8pt' : ($spalten <= 12 ? '7pt' : '6pt'));
$prozent = static fn(float $g): string => number_format($g / $summeGewichte * 100, 2, '.', '') . '%';
?>
<table class="werkliste" style="font-size:<?= $schrift ?>;">
    <thead>
        <tr>
            <th style="width:<?= $prozent(0.6) ?>;">#</th>
            <?php foreach ($pdfFelder as $n => $f): ?>
                <th class="<?= $f['typ'] === 'geld' ? 'num' : '' ?>" style="width:<?= $prozent($gewichte[$n]) ?>;"><?= Helpers::e(t($f['label'])) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($werke as $i => $w): ?>
    <?php
        // Zeile überspringen wenn komplett leer und leer_ausblenden aktiv
        if ($leerAusblenden) {
            $hatWert = false;
            foreach ($pdfFelder as $f) {
                if (($w[$f['key']] ?? '') !== '' && ($w[$f['key']] ?? null) !== null) {
                    $hatWert = true;
                    break;
                }
            }
            if (!$hatWert) continue;
        }
        $zeilenZahl++;
    ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <?php foreach ($pdfFelder as $f): ?>
            <?php
                $val = $w[$f['key']] ?? null;
                $leer = $val === null || $val === '';
                if ($f['typ'] === 'geld') {
                    if (!$leer) {
                        $summen[$f['key']] += (float) $val;
                        echo '<td class="num">' . Helpers::formatGeld((float) $val) . '</td>';
                    } else {
                        $ohneAngabe[$f['key']]++;
                        echo '<td class="num">' . ($leerAusblenden ? '' : '–') . '</td>';
                    }
                } elseif ($f['typ'] === 'langtext') {
                    $anzeige = $leer ? ($leerAusblenden ? '' : '–') : Helpers::e(mb_strimwidth((string) $val, 0, 120, '…'));
                    echo '<td>' . $anzeige . '</td>';
                } else {
                    $anzeige = $leer ? ($leerAusblenden ? '' : '–') : Helpers::e((string) $val);
                    echo '<td>' . $anzeige . '</td>';
                }
            ?>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <?php if ($zeigeSumme && $geldFelder): ?>
    <tfoot>
        <tr class="summe-zeile">
            <td></td>
            <?php foreach ($pdfFelder as $f): ?>
            <?php if ($f['typ'] === 'geld'): ?>
                <td class="num">
                    <?= Helpers::formatGeld($summen[$f['key']]) ?>
                    <?php if ($ohneAngabe[$f['key']] > 0): ?>
                        <br><span style="font-size:0.85em; font-weight:normal;"><?= Helpers::e(t('pdf.ohne_angabe', ['m' => $ohneAngabe[$f['key']]])) ?></span>
                    <?php endif; ?>
                </td>
            <?php else: ?>
                <td></td>
            <?php endif; ?>
            <?php endforeach; ?>
        </tr>
    </tfoot>
    <?php endif; ?>
</table>
<?php else: ?>
<?php
// ── Einzelblatt-Layout ────────────────────────────────────────────────────────
foreach ($werke as $i => $w):
?>
<div class="seite">
    <div class="kopf"><?= Helpers::e($gruppe['name']) ?> · <?= Helpers::e(t('pdf.werk_n_von_m', ['n' => $i + 1, 'm' => count($werke)])) ?></div>
    <?php if ($zeigeBild): ?>
    <div class="bild-rahmen">
        <?php if ($w['bild_data_uri']): ?>
            <img src="<?= $w['bild_data_uri'] ?>">
        <?php else: ?>
            <div class="kein-bild"><?= Helpers::e(t('pdf.kein_bild')) ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($zeigeTitelblock): ?>
    <div class="etikett">
        <div class="maler"><?= Helpers::e($w['maler']) ?></div>
        <div class="titel"><?= Helpers::e($w['titel']) ?></div>
        <div class="meta"><?= Helpers::e(Helpers::werkMeta($w['technik'] ?? null, $w['entstehungsjahr'] ?? null)) ?></div>
    </div>
    <?php endif; ?>
    <table class="daten">
        <?php foreach ($tabellenFelder as $feld): ?>
        <?php
            $wert = $w[$feld['key']] ?? null;
            if ($leerAusblenden && ($wert === null || $wert === '')) continue;
            $anzeige = match ($feld['typ']) {
                'geld' => Helpers::formatGeld($wert !== null ? (float) $wert : null),
                default => Helpers::e((string) ($wert ?? '')),
            };
            if ($leerAusblenden && $anzeige === '') continue;
        ?>
        <tr><td class="label"><?= Helpers::e(t($feld['label'])) ?></td><td><?= $anzeige ?></td></tr>
        <?php endforeach; ?>
    </table>
    <?php foreach ($langtextFelder as $feld): ?>
    <?php
        $wert = trim((string) ($w[$feld['key']] ?? ''));
        if ($wert === '') continue;
    ?>
    <div class="beschreibung-label"><?= Helpers::e(t($feld['label'])) ?></div>
    <div class="beschreibung"><?= nl2br(Helpers::e($wert)) ?></div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php if (!$werke): ?>
<div class="seite"><p><?= Helpers::e(t('pdf.gruppe_leer')) ?></p></div>
<?php endif; ?>
</body>
</html>
