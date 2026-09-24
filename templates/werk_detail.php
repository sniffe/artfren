<?php
/** @var array $werk */
/** @var array $bilder */
declare(strict_types=1);

use App\Helpers;

$hauptbild = $bilder[0] ?? null;
$bildUrl = $hauptbild ? Helpers::bildUrl((int) $hauptbild['id'], $hauptbild['dateiname'], 'g') : null;
$originalUrl = $hauptbild ? Helpers::bildUrl((int) $hauptbild['id'], $hauptbild['dateiname'], 'o') : null;
?>
<div class="toolbar">
    <p class="text-klein" style="margin:0;"><a href="/" data-zurueck>← zurück</a></p>
    <?php if (\App\Auth::isAdmin($aktuellerBenutzer)): ?>
        <a href="/werk_bearbeiten.php?id=<?= (int) $werk['id'] ?>&amp;zurueck=<?= rawurlencode('/werk.php?id=' . (int) $werk['id']) ?>" class="btn btn--primaer">Bearbeiten</a>
    <?php endif; ?>
</div>
<div class="vitrine-detail">
    <div class="passepartout">
        <?php if ($bildUrl): ?>
            <img src="<?= Helpers::e($bildUrl) ?>" alt="<?= Helpers::e($werk['titel']) ?>">
        <?php elseif ($hauptbild): ?>
            <span class="passepartout--leer">Bilddatei „<?= Helpers::e($hauptbild['dateiname']) ?>“ fehlt noch</span>
        <?php else: ?>
            <span class="passepartout--leer">Kein Bild hinterlegt</span>
        <?php endif; ?>
    </div>
    <div class="werk-etikett">
        <div class="maler"><?= Helpers::e($werk['maler']) ?></div>
        <div class="titel"><?= Helpers::e($werk['titel']) ?></div>
        <div class="meta"><?= Helpers::e($werk['technik']) ?><?= $werk['entstehungsjahr'] ? ', ' . (int) $werk['entstehungsjahr'] : '' ?></div>

        <dl>
            <dt>Ort</dt><dd><?= Helpers::e($werk['ort']) ?></dd>
            <dt>Format</dt><dd><?= Helpers::e($werk['format']) ?></dd>
            <dt>Technik</dt><dd><?= Helpers::e($werk['technik']) ?></dd>
            <dt>Entstehungsjahr</dt><dd><?= Helpers::e((string) $werk['entstehungsjahr']) ?></dd>
            <dt>Ankaufjahr</dt><dd><?= Helpers::e((string) $werk['ankaufjahr']) ?></dd>
            <dt>Ankauf</dt><dd><?= Helpers::e($werk['ankauf']) ?></dd>
            <dt>Ankaufswert</dt><dd><?= Helpers::formatGeld($werk['ankaufswert'] !== null ? (float) $werk['ankaufswert'] : null) ?></dd>
            <dt>Wert</dt><dd><?= Helpers::formatGeld($werk['wert'] !== null ? (float) $werk['wert'] : null) ?></dd>
            <dt>Typ</dt><dd><?= Helpers::e($werk['werktyp']) ?></dd>
            <?php if ($werk['status_farbe']): ?>
            <dt>Status</dt><dd><span class="status-punkt status-punkt--<?= Helpers::e($werk['status_farbe']) ?>"></span> <?= Helpers::e(Helpers::statusLabel($werk['status_farbe'])) ?></dd>
            <?php endif; ?>
        </dl>
        <?php if ($originalUrl): ?>
            <p class="mt-m"><a href="<?= Helpers::e($originalUrl) ?>" target="_blank" rel="noopener" class="btn btn--klein">Originalbild öffnen</a></p>
        <?php endif; ?>
    </div>
</div>
