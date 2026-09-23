<?php
/** @var array $werk */
/** @var array $bilder */
declare(strict_types=1);

use App\Helpers;

$hauptbild = $bilder[0]['dateiname'] ?? null;
$bildUrl = Helpers::bildUrl($hauptbild);
?>
<div class="vitrine-detail">
    <div class="passepartout">
        <?php if ($bildUrl): ?>
            <img src="<?= Helpers::e($bildUrl) ?>" alt="<?= Helpers::e($werk['titel']) ?>">
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
            <dt>Status</dt><dd><span class="status-punkt status-punkt--<?= Helpers::e($werk['status_farbe']) ?>"></span> <?= Helpers::e($werk['status_farbe']) ?></dd>
            <?php endif; ?>
        </dl>
    </div>
</div>
