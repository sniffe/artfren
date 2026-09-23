<?php
/** @var string|null $fehler */
declare(strict_types=1);

use App\Helpers;
?>
<div class="login-seite">
    <div class="login-karte">
        <h1><?= Helpers::e(APP_NAME) ?></h1>
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
