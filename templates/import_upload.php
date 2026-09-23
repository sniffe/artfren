<?php
declare(strict_types=1);

use App\Helpers;
?>
<h2>CSV-Import</h2>
<p class="text-sekundaer">Lade die aus dem Excel-Blatt „Galerie“ exportierte CSV-Datei hoch. Im nächsten Schritt kannst du die Spaltenzuordnung prüfen und korrigieren.</p>

<div class="karte" style="max-width:520px;">
    <form method="post" action="/import.php" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="vorschau">
        <div class="feld">
            <label for="csv_datei">CSV-Datei</label>
            <input type="file" id="csv_datei" name="csv_datei" accept=".csv,text/csv" required>
        </div>
        <button type="submit" class="btn btn--primaer">Weiter zur Vorschau</button>
    </form>
</div>
