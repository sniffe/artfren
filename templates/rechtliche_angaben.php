<?php
/** @var string $impressumUrl */
/** @var string $impressumText */
/** @var string $datenschutzUrl */
/** @var string $datenschutzText */
declare(strict_types=1);

use App\Helpers;
?>
<h2><?= Helpers::e(t('recht.titel')) ?></h2>
<p class="text-klein text-sekundaer">
    <?= Helpers::e(t('recht.beschreibung')) ?>
    <strong><?= Helpers::e(t('recht.verantwortung')) ?></strong>
</p>

<form method="post">
    <?= Helpers::csrfField() ?>

    <div class="karte" style="max-width:820px;">
        <h3><?= Helpers::e(t('recht.impressum')) ?></h3>
        <div class="feld">
            <label for="impressum_url"><?= Helpers::e(t('recht.impressum_url')) ?> <span class="text-sekundaer"><?= Helpers::e(t('recht.impressum_url_hinweis')) ?></span></label>
            <input type="url" id="impressum_url" name="impressum_url"
                   value="<?= Helpers::e($impressumUrl) ?>"
                   placeholder="https://example.com/impressum">
        </div>
        <div class="feld">
            <label for="impressum_text"><?= Helpers::e(t('recht.impressum_text')) ?> <span class="text-sekundaer"><?= Helpers::e(t('recht.impressum_text_hinweis')) ?></span></label>
            <textarea id="impressum_text" name="impressum_text" rows="10"><?= Helpers::e($impressumText) ?></textarea>
        </div>
    </div>

    <div class="karte" style="max-width:820px;">
        <h3><?= Helpers::e(t('recht.datenschutz')) ?></h3>
        <div class="feld">
            <label for="datenschutz_url"><?= Helpers::e(t('recht.datenschutz_url')) ?> <span class="text-sekundaer"><?= Helpers::e(t('recht.impressum_url_hinweis')) ?></span></label>
            <input type="url" id="datenschutz_url" name="datenschutz_url"
                   value="<?= Helpers::e($datenschutzUrl) ?>"
                   placeholder="https://example.com/datenschutz">
        </div>
        <div class="feld">
            <label for="datenschutz_text"><?= Helpers::e(t('recht.datenschutz_text')) ?> <span class="text-sekundaer"><?= Helpers::e(t('recht.impressum_text_hinweis')) ?></span></label>
            <textarea id="datenschutz_text" name="datenschutz_text" rows="10"><?= Helpers::e($datenschutzText) ?></textarea>
        </div>
    </div>

    <div style="max-width:820px; margin-top:var(--abstand-m);">
        <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('allg.speichern')) ?></button>
    </div>
</form>
