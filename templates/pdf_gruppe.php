<?php
/** @var array $gruppe */
/** @var array $werke  jeweils mit 'bild_data_uri' */
declare(strict_types=1);

use App\Helpers;
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
    table.daten { width: 100%; margin-top: 24px; border-collapse: collapse; }
    table.daten td { padding: 4px 8px; border-bottom: 1px solid #E2E0DC; font-size: 10pt; }
    table.daten td.label { color: #6B6862; width: 160px; }
</style>
</head>
<body>
<?php foreach ($werke as $i => $w): ?>
<div class="seite">
    <div class="kopf"><?= Helpers::e($gruppe['name']) ?> · Werk <?= $i + 1 ?> von <?= count($werke) ?></div>
    <div class="bild-rahmen">
        <?php if ($w['bild_data_uri']): ?>
            <img src="<?= $w['bild_data_uri'] ?>">
        <?php else: ?>
            <div class="kein-bild">Kein Bild hinterlegt</div>
        <?php endif; ?>
    </div>
    <div class="etikett">
        <div class="maler"><?= Helpers::e($w['maler']) ?></div>
        <div class="titel"><?= Helpers::e($w['titel']) ?></div>
    </div>
    <table class="daten">
        <tr><td class="label">Ort</td><td><?= Helpers::e($w['ort']) ?></td></tr>
        <tr><td class="label">Format</td><td><?= Helpers::e($w['format']) ?></td></tr>
        <tr><td class="label">Technik</td><td><?= Helpers::e($w['technik']) ?></td></tr>
        <tr><td class="label">Entstehungsjahr</td><td><?= Helpers::e((string) $w['entstehungsjahr']) ?></td></tr>
        <tr><td class="label">Ankaufjahr</td><td><?= Helpers::e((string) $w['ankaufjahr']) ?></td></tr>
        <tr><td class="label">Ankauf</td><td><?= Helpers::e($w['ankauf']) ?></td></tr>
        <tr><td class="label">Ankaufswert</td><td><?= Helpers::formatGeld($w['ankaufswert'] !== null ? (float) $w['ankaufswert'] : null) ?></td></tr>
        <tr><td class="label">Wert</td><td><?= Helpers::formatGeld($w['wert'] !== null ? (float) $w['wert'] : null) ?></td></tr>
    </table>
</div>
<?php endforeach; ?>
<?php if (!$werke): ?>
<div class="seite"><p>Diese Gruppe enthält keine Werke.</p></div>
<?php endif; ?>
</body>
</html>
