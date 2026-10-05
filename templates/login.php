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
        <form method="post" action="/login.php">
            <?= Helpers::csrfField() ?>
            <div class="feld">
                <label for="benutzername">Benutzername</label>
                <input type="text" id="benutzername" name="benutzername" autocomplete="username" required autofocus>
            </div>
            <div class="feld">
                <label for="passwort">Passwort</label>
                <input type="password" id="passwort" name="passwort" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn--primaer" style="width:100%">Anmelden</button>
        </form>
    </div>
</div>
