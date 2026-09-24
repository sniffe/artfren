<?php
/** @var array $benutzer */
/** @var string|null $fehler */
declare(strict_types=1);

use App\Helpers;
?>
<h2>Mein Konto</h2>
<p class="text-sekundaer">Angemeldet als <strong><?= Helpers::e($benutzer['benutzername']) ?></strong> (<?= $benutzer['rolle'] === 'admin' ? 'Admin' : 'eingeschränkter Zugriff' ?>).</p>

<?php if ($fehler): ?>
    <div class="flash flash--fehler"><?= Helpers::e($fehler) ?></div>
<?php endif; ?>

<div class="karte" style="max-width:640px;">
    <h3>Meine Angaben</h3>
    <form method="post" action="/konto.php">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="kontakt">
        <div class="feldreihe">
            <div class="feld">
                <label for="echter_name">Name</label>
                <input type="text" id="echter_name" name="echter_name" value="<?= Helpers::e($benutzer['echter_name']) ?>" maxlength="100">
            </div>
            <div class="feld">
                <label for="email">E-Mail</label>
                <input type="email" id="email" name="email" value="<?= Helpers::e($benutzer['email']) ?>">
            </div>
        </div>
        <button type="submit" class="btn">Speichern</button>
    </form>
</div>

<div class="karte" style="max-width:640px;">
    <h3>Passwort ändern</h3>
    <form method="post" action="/konto.php">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="passwort">
        <div class="feld">
            <label for="aktuelles_passwort">Aktuelles Passwort</label>
            <input type="password" id="aktuelles_passwort" name="aktuelles_passwort" required autocomplete="current-password">
        </div>
        <div class="feldreihe">
            <div class="feld">
                <label for="passwort">Neues Passwort (mind. 8 Zeichen)</label>
                <input type="password" id="passwort" name="passwort" required minlength="8" autocomplete="new-password">
            </div>
            <div class="feld">
                <label for="passwort_wiederholen">Wiederholen</label>
                <input type="password" id="passwort_wiederholen" name="passwort_wiederholen" required minlength="8" autocomplete="new-password">
            </div>
        </div>
        <button type="submit" class="btn btn--primaer">Passwort ändern</button>
    </form>
</div>
