<?php
/** @var array $werke */
declare(strict_types=1);

use App\Helpers;
?>
<div class="toolbar">
    <h2 style="margin:0;"><?= Helpers::e(t('papierkorb.titel')) ?></h2>
</div>
<p class="text-sekundaer"><?= Helpers::e(t('papierkorb.beschreibung')) ?></p>

<?php if (!$werke): ?>
    <p class="text-sekundaer mt-m"><?= Helpers::e(t('papierkorb.leer')) ?></p>
<?php else: ?>
<table class="liste mt-m">
    <thead>
        <tr>
            <th><?= Helpers::e(t('papierkorb.bild')) ?></th>
            <th><?= Helpers::e(t('papierkorb.ort')) ?></th>
            <th><?= Helpers::e(t('feld.maler')) ?></th>
            <th><?= Helpers::e(t('feld.titel')) ?></th>
            <th><?= Helpers::e(t('papierkorb.geloescht_am')) ?></th>
            <th><?= Helpers::e(t('papierkorb.geloescht_von')) ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($werke as $w):
        $thumb = Helpers::bildUrl($w['bild_id'], $w['bild_dateiname'], 't'); ?>
        <tr>
            <td data-label="<?= Helpers::e(t('papierkorb.bild')) ?>">
                <?php if ($thumb): ?>
                    <img class="thumb" src="<?= Helpers::e($thumb) ?>" alt="" loading="lazy">
                <?php endif; ?>
            </td>
            <td data-label="<?= Helpers::e(t('papierkorb.ort')) ?>" class="text-klein"><?= Helpers::e($w['ort'] ?? '') ?></td>
            <td data-label="<?= Helpers::e(t('feld.maler')) ?>" class="text-klein"><?= Helpers::e($w['maler'] ?? '') ?></td>
            <td data-label="<?= Helpers::e(t('feld.titel')) ?>" class="text-klein"><?= Helpers::e($w['titel'] ?? '') ?></td>
            <td data-label="<?= Helpers::e(t('papierkorb.geloescht_am')) ?>" class="text-klein"><?= Helpers::e(Helpers::formatDatum($w['geloescht_am'] ?? '')) ?></td>
            <td data-label="<?= Helpers::e(t('papierkorb.geloescht_von')) ?>" class="text-klein"><?= Helpers::e($w['geloescht_von'] ?? '') ?></td>
            <td data-label="" style="white-space:nowrap;">
                <form method="post" action="/papierkorb.php" style="display:inline;">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="aktion" value="wiederherstellen">
                    <input type="hidden" name="werk_id" value="<?= (int) $w['id'] ?>">
                    <button type="submit" class="btn btn--klein"><?= Helpers::e(t('papierkorb.wiederherstellen')) ?></button>
                </form>
                <form method="post" action="/papierkorb.php" style="display:inline;"
                      data-bestaetigen="<?= Helpers::e(t('papierkorb.endgueltig_frage', ['titel' => $w['titel'] ?? t('werk.unbenannt')])) ?>">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="aktion" value="endgueltig_loeschen">
                    <input type="hidden" name="werk_id" value="<?= (int) $w['id'] ?>">
                    <button type="submit" class="btn btn--klein btn--gefahr"><?= Helpers::icon('papierkorb') ?> <?= Helpers::e(t('papierkorb.endgueltig')) ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
