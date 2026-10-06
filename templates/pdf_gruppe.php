<?php
/** @var array $gruppe */
/** @var array $werke  jeweils mit 'bild_data_uri' */
declare(strict_types=1);

use App\Felder;
use App\Helpers;

// Felder, die in der PDF-Tabelle erscheinen (in_pdf = true, kein Etikett-Feld).
$pdfFelder = array_filter(Felder::alle(), static fn(array $f): bool => $f['in_pdf']);
?>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; color: #1C1B1A; font-size: 11pt; }
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
    .beschreibung { padding: 6px 8px; font-size: 10pt; line-height: 1.4; }
</style>
</head>
<body>
<?php foreach ($werke as $i => $w): ?>
<div class="seite">
    <div class="kopf"><?= Helpers::e($gruppe['name']) ?> · <?= Helpers::e(t('pdf.werk_n_von_m', ['n' => $i + 1, 'm' => count($werke)])) ?></div>
    <div class="bild-rahmen">
        <?php if ($w['bild_data_uri']): ?>
            <img src="<?= $w['bild_data_uri'] ?>">
        <?php else: ?>
            <div class="kein-bild"><?= Helpers::e(t('pdf.kein_bild')) ?></div>
        <?php endif; ?>
    </div>
    <div class="etikett">
        <div class="maler"><?= Helpers::e($w['maler']) ?></div>
        <div class="titel"><?= Helpers::e($w['titel']) ?></div>
        <div class="meta"><?= Helpers::e(Helpers::werkMeta($w['technik'] ?? null, $w['entstehungsjahr'] ?? null)) ?></div>
    </div>
    <table class="daten">
        <?php foreach ($pdfFelder as $feld): ?>
        <?php
            $wert = $w[$feld['key']] ?? null;
            if ($wert === null || $wert === '') continue;
            if ($feld['typ'] === 'langtext') {
                // Beschreibung als Absatz unter der Tabelle
                continue;
            }
            $anzeige = match ($feld['typ']) {
                'geld' => Helpers::formatGeld($wert !== null ? (float) $wert : null),
                default => Helpers::e((string) $wert),
            };
            if ($anzeige === '') continue;
        ?>
        <tr><td class="label"><?= Helpers::e(t($feld['label'])) ?></td><td><?= $anzeige ?></td></tr>
        <?php endforeach; ?>
    </table>
    <?php
    // Beschreibung (oeffentlich) als eigener Absatz nach der Tabelle
    $beschr = trim((string) ($w['beschreibung_oeffentlich'] ?? ''));
    if ($beschr !== ''):
    ?>
    <div class="beschreibung"><?= nl2br(Helpers::e($beschr)) ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php if (!$werke): ?>
<div class="seite"><p><?= Helpers::e(t('pdf.gruppe_leer')) ?></p></div>
<?php endif; ?>
</body>
</html>
