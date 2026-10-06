<?php
/** @var string|null $fehler */
declare(strict_types=1);

use App\Helpers;
?>
<div class="login-seite">
    <div class="login-karte">
        <?php if ($hatLogo ?? false): ?>
            <img src="/logo.php" alt="<?= Helpers::e(\App\Einstellungen::appName()) ?>" style="max-height:60px; max-width:200px; object-fit:contain; display:block; margin:0 auto var(--abstand-m);">
        <?php else: ?>
            <h1><?= Helpers::e(\App\Einstellungen::appName()) ?></h1>
        <?php endif; ?>
        <?php if ($fehler): ?>
            <div class="flash flash--fehler"><?= Helpers::e($fehler) ?></div>
        <?php endif; ?>
        <p class="text-sekundaer text-klein" style="text-align:right;margin-bottom:var(--abstand-s)"><?php
            $langLinks = [];
            foreach (\App\I18n::SPRACHEN as $code => $name) {
                $langLinks[] = $code !== \App\I18n::aktiv()
                    ? '<a href="?lang=' . Helpers::e($code) . '">' . Helpers::e($name) . '</a>'
                    : '<strong>' . Helpers::e($name) . '</strong>';
            }
            echo implode(' | ', $langLinks);
        ?></p>
        <form method="post" action="/login.php">
            <?= Helpers::csrfField() ?>
            <div class="feld">
                <label for="benutzername"><?= Helpers::e(t('login.benutzername')) ?></label>
                <input type="text" id="benutzername" name="benutzername" autocomplete="username" required autofocus>
            </div>
            <div class="feld">
                <label for="passwort"><?= Helpers::e(t('login.passwort')) ?></label>
                <input type="password" id="passwort" name="passwort" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn--primaer" style="width:100%"><?= Helpers::e(t('login.anmelden')) ?></button>
        </form>
    </div>
</div>
