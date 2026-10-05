<?php
/** @var string $token */
/** @var string|null $fehler */
declare(strict_types=1);

use App\Helpers;
?>
<div class="web-passwort">
    <h1>Galerie öffnen</h1>
    <p>Diese Galerie ist mit einem Passwort geschützt.</p>
    <?php if ($fehler): ?>
        <div class="web-fehler-inline"><?= Helpers::e($fehler) ?></div>
    <?php endif; ?>
    <form method="post" action="/w/<?= Helpers::e($token) ?>/passwort" class="web-passwort__form">
        <label for="passwort">Passwort</label>
        <input type="password" id="passwort" name="passwort" autofocus autocomplete="current-password" required>
        <button type="submit" class="web-btn">Weiter</button>
    </form>
</div>
