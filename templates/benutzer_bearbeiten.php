<?php
/** @var array $ziel */
/** @var string|null $fehler */
/** @var array $gruppen */
/** @var int[] $ausgewaehlt */
declare(strict_types=1);

use App\Helpers;

$zielId = (int) $ziel['id'];
?>
<p class="text-klein"><a href="/benutzer.php">← Benutzerverwaltung</a></p>
<h2>Benutzer „<?= Helpers::e($ziel['benutzername']) ?>“ bearbeiten</h2>

<?php if ($fehler): ?>
    <div class="flash flash--fehler"><?= Helpers::e($fehler) ?></div>
<?php endif; ?>

<div class="karte" style="max-width:820px;">
    <h3>Stammdaten und Rechte</h3>
    <form method="post" action="/benutzer_bearbeiten.php?id=<?= $zielId ?>">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="speichern">
        <input type="hidden" name="id" value="<?= $zielId ?>">
        <div class="feldreihe">
            <div class="feld">
                <label for="echter_name">Echter Name</label>
                <input type="text" id="echter_name" name="echter_name" value="<?= Helpers::e($ziel['echter_name']) ?>" maxlength="100">
            </div>
            <div class="feld">
                <label for="email">E-Mail</label>
                <input type="email" id="email" name="email" value="<?= Helpers::e($ziel['email']) ?>">
            </div>
            <div class="feld">
                <label for="rolle">Rolle</label>
                <select id="rolle" name="rolle" data-rolle-umschalter="#gruppen-auswahl-bearbeiten">
                    <option value="eingeschraenkt" <?= $ziel['rolle'] !== 'admin' ? 'selected' : '' ?>>Eingeschränkt (nur zugewiesene Gruppen)</option>
                    <option value="admin" <?= $ziel['rolle'] === 'admin' ? 'selected' : '' ?>>Admin (voller Zugriff)</option>
                </select>
            </div>
        </div>
        <?php $auswahlId = 'gruppen-auswahl-bearbeiten'; require __DIR__ . '/_gruppen_auswahl.php'; ?>
        <button type="submit" class="btn btn--primaer">Speichern</button>
    </form>
</div>

<div class="karte" style="max-width:820px;">
    <h3>Neues Passwort setzen</h3>
    <p class="text-klein text-sekundaer">Für vergessene Passwörter. Der Benutzer wird dabei auf allen Geräten abgemeldet und eine eventuelle Kontosperre aufgehoben.</p>
    <form method="post" action="/benutzer_bearbeiten.php?id=<?= $zielId ?>" autocomplete="off">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="passwort">
        <input type="hidden" name="id" value="<?= $zielId ?>">
        <div class="feldreihe">
            <div class="feld">
                <label for="passwort">Neues Passwort</label>
                <input type="password" id="passwort" name="passwort" required minlength="8" autocomplete="new-password">
            </div>
            <div class="feld">
                <label for="passwort_wiederholen">Wiederholen</label>
                <input type="password" id="passwort_wiederholen" name="passwort_wiederholen" required minlength="8" autocomplete="new-password">
            </div>
        </div>
        <button type="submit" class="btn">Passwort setzen</button>
    </form>
</div>
