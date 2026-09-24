<?php
/** @var int $anzahlWerke */
/** @var array{dateien: string[], fehlend: string[], groesse: int} $bilder */
declare(strict_types=1);

use App\Helpers;
?>
<h2>Gesamtexport</h2>
<p class="text-sekundaer">Exportiert alle <?= $anzahlWerke ?> Werke, unabhängig von Gruppen. Excel-Datei und Bilder-ZIP gehören zusammen: Die Spalte „Dateiname“ nennt für jedes Werk die Bilddatei im ZIP.</p>

<div class="feldreihe">
    <div class="karte" style="flex:1;min-width:260px;">
        <h3>1. Tabelle (Excel)</h3>
        <p class="text-klein text-sekundaer">Alle Felder inklusive Status und Dateiname. Spalten wie beim Import.</p>
        <a href="/export_gesamt.php?format=xlsx" class="btn btn--primaer">Excel herunterladen</a>
        <a href="/export_gesamt.php?format=csv" class="btn btn--klein">als CSV</a>
    </div>
    <div class="karte" style="flex:1;min-width:260px;">
        <h3>2. Bilder (ZIP)</h3>
        <p class="text-klein text-sekundaer">
            <?= count($bilder['dateien']) ?> Bilddateien, ca. <?= Helpers::formatGroesse($bilder['groesse']) ?>.
            <?php if ($bilder['fehlend']): ?>
                <?= count($bilder['fehlend']) ?> verknüpfte Dateien fehlen auf dem Server – sie sind im ZIP in <code>FEHLENDE_BILDER.txt</code> aufgelistet.
            <?php endif; ?>
        </p>
        <?php if ($bilder['dateien'] || $bilder['fehlend']): ?>
            <a href="/export_gesamt.php?format=bilder" class="btn btn--primaer">Bilder-ZIP herunterladen</a>
        <?php else: ?>
            <p class="text-klein text-sekundaer">Noch keinem Werk ist ein Bild zugeordnet.</p>
        <?php endif; ?>
    </div>
</div>

<div class="karte mt-m">
    <h3>Wieder einspielen (z. B. auf einem neuen Server)</h3>
    <ol class="text-klein">
        <li>Unter <a href="/bilder_upload.php">Import → Bilder hochladen</a> das Bilder-ZIP hochladen (oder den ZIP-Inhalt per FTP in den Ordner <code>bilder/</code> kopieren).</li>
        <li>Unter <a href="/import.php">Import → Tabelle importieren</a> die Excel-Datei hochladen – die Bilder werden über die Spalte „Dateiname“ automatisch zugeordnet.</li>
    </ol>
    <p class="text-klein text-sekundaer">Die Reihenfolge ist egal: Werden die Bilder erst nach der Tabelle hochgeladen, erscheinen sie trotzdem automatisch. Gruppen, Benutzer und Einstellungen sind darin nicht enthalten – dafür gibt es das <a href="/backup.php">Backup</a>.</p>
</div>
