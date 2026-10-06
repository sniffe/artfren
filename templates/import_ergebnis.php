<?php
/** @var array $abgleich */
/** @var bool $bearbeiteteUeberschrieben */
declare(strict_types=1);

use App\Helpers;

$aktiverReiter = 'tabelle';
require __DIR__ . '/_import_reiter.php';
?>
<h2><?= Helpers::e(t('import.abgeschlossen_titel')) ?></h2>

<div class="kennzahlen">
    <div class="karte"><strong><?= count($abgleich['neu']) ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_neu')) ?></span></div>
    <div class="karte"><strong><?= count($abgleich['geaendert']) + ($bearbeiteteUeberschrieben ? count($abgleich['geschuetzt']) : 0) ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_aktual')) ?></span></div>
    <?php if ($abgleich['geschuetzt'] && !$bearbeiteteUeberschrieben): ?><div class="karte"><strong><?= count($abgleich['geschuetzt']) ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_beibehalten')) ?></span></div><?php endif; ?>
    <div class="karte"><strong><?= $abgleich['unveraendert'] ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_unveraendert')) ?></span></div>
    <div class="karte"><strong><?= count($abgleich['fehlerBild']) ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_bild_fehlt')) ?></span></div>
    <?php if ($abgleich['imPapierkorb']): ?><div class="karte"><strong><?= count($abgleich['imPapierkorb']) ?></strong><span class="text-klein text-sekundaer"><?= Helpers::e(t('import.kachel_pk_ueberspr')) ?></span></div><?php endif; ?>
</div>

<div class="mt-l toolbar-aktionen">
    <a href="/werke.php" class="btn btn--primaer"><?= Helpers::e(t('import.zur_werkliste')) ?></a>
    <?php if ($abgleich['fehlerBild']): ?><a href="/bilder_upload.php" class="btn"><?= Helpers::e(t('import.fehlende_bilder_hla')) ?></a><?php endif; ?>
    <a href="/orte.php" class="btn"><?= Helpers::e(t('import.orte_pruefen')) ?></a>
    <a href="/backup.php" class="btn"><?= Helpers::e(t('import.backup_erstellen')) ?></a>
</div>
