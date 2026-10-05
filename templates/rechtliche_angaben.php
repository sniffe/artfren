<?php
/** @var string $impressumUrl */
/** @var string $impressumText */
/** @var string $datenschutzUrl */
/** @var string $datenschutzText */
declare(strict_types=1);

use App\Helpers;
?>
<h2>Rechtliche Angaben</h2>
<p class="text-klein text-sekundaer">
    Impressum und Datenschutzerklärung erscheinen im Footer öffentlicher Galerien.
    Eine externe URL leitet direkt weiter; reiner Text wird auf einer eigenen Unterseite angezeigt
    (<code>/w/impressum</code> bzw. <code>/w/datenschutz</code>).
    <strong>Der Inhalt liegt in Ihrer Verantwortung.</strong>
</p>

<form method="post">
    <?= Helpers::csrfField() ?>

    <div class="karte" style="max-width:820px;">
        <h3>Impressum</h3>
        <div class="feld">
            <label for="impressum_url">Externe URL <span class="text-sekundaer">(leer = eigene Unterseite nutzen)</span></label>
            <input type="url" id="impressum_url" name="impressum_url"
                   value="<?= Helpers::e($impressumUrl) ?>"
                   placeholder="https://example.com/impressum">
        </div>
        <div class="feld">
            <label for="impressum_text">Text <span class="text-sekundaer">(nur relevant wenn keine URL eingetragen)</span></label>
            <textarea id="impressum_text" name="impressum_text" rows="10"><?= Helpers::e($impressumText) ?></textarea>
        </div>
    </div>

    <div class="karte" style="max-width:820px;">
        <h3>Datenschutzerklärung</h3>
        <div class="feld">
            <label for="datenschutz_url">Externe URL <span class="text-sekundaer">(leer = eigene Unterseite nutzen)</span></label>
            <input type="url" id="datenschutz_url" name="datenschutz_url"
                   value="<?= Helpers::e($datenschutzUrl) ?>"
                   placeholder="https://example.com/datenschutz">
        </div>
        <div class="feld">
            <label for="datenschutz_text">Text <span class="text-sekundaer">(nur relevant wenn keine URL eingetragen)</span></label>
            <textarea id="datenschutz_text" name="datenschutz_text" rows="10"><?= Helpers::e($datenschutzText) ?></textarea>
        </div>
    </div>

    <div style="max-width:820px; margin-top:var(--abstand-m);">
        <button type="submit" class="btn btn--primaer">Speichern</button>
    </div>
</form>
