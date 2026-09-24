<?php
/** @var array $abgleich */
declare(strict_types=1);

$aktiverReiter = 'tabelle';
require __DIR__ . '/_import_reiter.php';
?>
<h2>Import abgeschlossen</h2>

<div class="kennzahlen">
    <div class="karte"><strong><?= count($abgleich['neu']) ?></strong><span class="text-klein text-sekundaer">neu</span></div>
    <div class="karte"><strong><?= count($abgleich['geaendert']) ?></strong><span class="text-klein text-sekundaer">aktualisiert</span></div>
    <div class="karte"><strong><?= $abgleich['unveraendert'] ?></strong><span class="text-klein text-sekundaer">unverändert</span></div>
    <div class="karte"><strong><?= count($abgleich['fehlerBild']) ?></strong><span class="text-klein text-sekundaer">Bilddatei fehlt noch</span></div>
</div>

<div class="mt-l toolbar-aktionen">
    <a href="/werke.php" class="btn btn--primaer">Zur Werkliste</a>
    <?php if ($abgleich['fehlerBild']): ?><a href="/bilder_upload.php" class="btn">Fehlende Bilder hochladen</a><?php endif; ?>
    <a href="/orte.php" class="btn">Orte prüfen</a>
    <a href="/backup.php" class="btn">Backup erstellen</a>
</div>
