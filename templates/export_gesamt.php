<?php
/** @var int $anzahlWerke */
/** @var array{dateien: string[], fehlend: string[], groesse: int} $bilder */
declare(strict_types=1);

use App\Helpers;
?>
<h2><?= Helpers::e(t('export.titel')) ?></h2>
<p class=”text-sekundaer”><?= Helpers::e(t('export.beschreibung', ['n' => $anzahlWerke])) ?></p>

<div class=”feldreihe”>
    <div class=”karte” style=”flex:1;min-width:260px;”>
        <h3><?= Helpers::e(t('export.tabelle_titel')) ?></h3>
        <p class=”text-klein text-sekundaer”><?= Helpers::e(t('export.tabelle_beschreibung')) ?></p>
        <a href=”/export_gesamt.php?format=xlsx” class=”btn btn--primaer”><?= Helpers::e(t('export.excel_herunterladen')) ?></a>
        <a href=”/export_gesamt.php?format=csv” class=”btn btn--klein”><?= Helpers::e(t('export.als_csv')) ?></a>
    </div>
    <div class=”karte” style=”flex:1;min-width:260px;”>
        <h3><?= Helpers::e(t('export.bilder_titel')) ?></h3>
        <p class=”text-klein text-sekundaer”>
            <?= Helpers::e(t('export.bilder_info', ['n' => count($bilder['dateien']), 'groesse' => Helpers::formatGroesse($bilder['groesse'])])) ?>
            <?php if ($bilder['fehlend']): ?>
                <?= Helpers::e(t('export.bilder_fehlend', ['n' => count($bilder['fehlend'])])) ?>
            <?php endif; ?>
        </p>
        <?php if ($bilder['dateien'] || $bilder['fehlend']): ?>
            <a href=”/export_gesamt.php?format=bilder” class=”btn btn--primaer”><?= Helpers::e(t('export.bilder_zip')) ?></a>
        <?php else: ?>
            <p class=”text-klein text-sekundaer”><?= Helpers::e(t('export.keine_bilder')) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class=”karte mt-m”>
    <h3><?= Helpers::e(t('export.wiedereinspielen_titel')) ?></h3>
    <ol class=”text-klein”>
        <li><?= Helpers::e(t('export.wiedereinspielen_1')) ?></li>
        <li><?= Helpers::e(t('export.wiedereinspielen_2')) ?></li>
    </ol>
    <p class=”text-klein text-sekundaer”><?= Helpers::e(t('export.wiedereinspielen_hinweis')) ?></p>
</div>
