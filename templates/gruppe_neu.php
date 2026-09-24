<?php
declare(strict_types=1);

use App\Helpers;
?>
<h2>Neue Gruppe anlegen</h2>

<div data-auswahl-schluessel="auswahl_neu" data-auswahl-start="null" class="karte" style="max-width:480px;">
    <p><strong data-auswahl-zaehler>0</strong> Werke aktuell ausgewählt.</p>
    <p data-auswahl-leer-hinweis class="text-sekundaer text-klein">Noch keine Werke ausgewählt – bitte zuerst in der Werkliste auswählen.</p>

    <form method="post" action="/gruppen.php" data-auswahl-formular>
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="anlegen">
        <div class="feld">
            <label for="name">Gruppenname</label>
            <input type="text" id="name" name="name" required autofocus maxlength="200">
        </div>
        <button type="submit" class="btn btn--primaer">Gruppe speichern</button>
        <a href="/werke.php" class="btn">Auswahl bearbeiten</a>
    </form>
</div>
<script src="/assets/auswahl.js"></script>
