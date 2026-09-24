<?php
/** @var int $maxGroesse */
declare(strict_types=1);

use App\Helpers;

$aktiverReiter = 'tabelle';
require __DIR__ . '/_import_reiter.php';
?>
<div class="karte" style="max-width:640px;">
    <h3>Tabelle importieren</h3>
    <p class="text-sekundaer">Die Excel-Datei kann <strong>direkt</strong> hochgeladen werden (erstes Tabellenblatt, z. B. „Galerie“). Die Kopfzeile wird automatisch gefunden, Legenden- und Summenzeilen werden übersprungen, und farbig markierte Zeilen werden als Status übernommen. CSV-Dateien funktionieren ebenfalls.</p>
    <form method="post" action="/import.php" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="hochladen">
        <div class="feld">
            <label for="datei">Datei (.xlsx oder .csv, höchstens <?= Helpers::formatGroesse($maxGroesse) ?>)</label>
            <input type="file" id="datei" name="datei" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required>
        </div>
        <button type="submit" class="btn btn--primaer">Weiter zur Vorschau</button>
    </form>
    <p class="text-klein text-sekundaer mt-m">Beim erneuten Import werden Werke anhand von Ort, Maler und Titel wiedererkannt und nur tatsächliche Änderungen übernommen. Vor dem Übernehmen wird angezeigt, was sich ändert.</p>
</div>
