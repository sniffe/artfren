<?php
declare(strict_types=1);

use App\Helpers;
?>
<h2><?= Helpers::e(t('gruppe_neu.titel')) ?></h2>

<div data-auswahl-schluessel="auswahl_neu" data-auswahl-start="null" class="karte" style="max-width:480px;">
    <p><strong data-auswahl-zaehler>0</strong> <?= Helpers::e(t('gruppe_neu.zaehler_suffix')) ?></p>
    <p data-auswahl-leer-hinweis class="text-sekundaer text-klein"><?= Helpers::e(t('gruppe_neu.leer_hinweis')) ?></p>

    <form method="post" action="/gruppen.php" data-auswahl-formular>
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="anlegen">
        <div class="feld">
            <label for="name"><?= Helpers::e(t('gruppe_neu.gruppenname')) ?></label>
            <input type="text" id="name" name="name" required autofocus maxlength="200">
        </div>
        <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('gruppe_neu.speichern')) ?></button>
        <a href="/werke.php" class="btn"><?= Helpers::e(t('gruppe_neu.auswahl_bearbeiten')) ?></a>
    </form>
</div>
<script src="<?= \App\Helpers::asset('/assets/auswahl.js') ?>"></script>
