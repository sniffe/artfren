<?php
/** @var string $token */
/** @var string|null $fehler */
declare(strict_types=1);

use App\Helpers;
?>
<div class="web-passwort">
    <h1><?= Helpers::e(t('pubweb.galerie_oeffnen')) ?></h1>
    <p><?= Helpers::e(t('pubweb.passwort_geschuetzt')) ?></p>
    <?php if ($fehler): ?>
        <div class="web-fehler-inline"><?= Helpers::e($fehler) ?></div>
    <?php endif; ?>
    <form method="post" action="/w/<?= Helpers::e($token) ?>/passwort" class="web-passwort__form">
        <label for="passwort"><?= Helpers::e(t('pubweb.passwort_label')) ?></label>
        <input type="password" id="passwort" name="passwort" autofocus autocomplete="current-password" required>
        <button type="submit" class="web-btn"><?= Helpers::e(t('pubweb.passwort_btn')) ?></button>
    </form>
</div>
