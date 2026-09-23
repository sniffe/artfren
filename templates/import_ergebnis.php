<?php
/** @var int $anzahlNeu */
/** @var int $anzahlGeaendert */
/** @var int $anzahlUnveraendert */
/** @var int $anzahlFehler */
/** @var array $fehlerBild */
declare(strict_types=1);

use App\Helpers;
?>
<h2>Import abgeschlossen</h2>

<div class="karte" style="max-width:520px;">
    <p><strong><?= $anzahlNeu ?></strong> neu, <strong><?= $anzahlGeaendert ?></strong> aktualisiert, <strong><?= $anzahlUnveraendert ?></strong> unverändert, <strong><?= $anzahlFehler ?></strong> Fehler.</p>
    <?php if ($fehlerBild): ?>
        <p class="text-klein text-sekundaer">Folgende Werke wurden ohne Bild importiert, da die Datei im Bilder-Ordner fehlt:</p>
        <ul class="text-klein">
            <?php foreach ($fehlerBild as $eintrag): $d = $eintrag['daten']; ?>
                <li><?= Helpers::e(trim(($d['ort'] ?? '') . ' – ' . ($d['maler'] ?? '') . ' – ' . ($d['titel'] ?? ''), ' –')) ?> (<?= Helpers::e((string) $d['bild_dateiname']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <a href="/werke.php" class="btn btn--primaer">Zur Werkliste</a>
    <a href="/import.php" class="btn">Weiteren Import starten</a>
</div>
