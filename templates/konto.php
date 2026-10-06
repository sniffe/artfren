<?php
/** @var array $benutzer */
/** @var string|null $fehler */
declare(strict_types=1);

use App\Helpers;
?>
<h2><?= Helpers::e(t('konto.titel')) ?></h2>
<p class="text-sekundaer"><?= Helpers::e(t('konto.angemeldet_als')) ?> <strong><?= Helpers::e($benutzer['benutzername']) ?></strong> (<?= $benutzer['rolle'] === 'admin' ? Helpers::e(t('konto.rolle_admin')) : Helpers::e(t('konto.rolle_eingeschraenkt')) ?>).</p>

<?php if ($fehler): ?>
    <div class="flash flash--fehler"><?= Helpers::e($fehler) ?></div>
<?php endif; ?>

<div class="karte" style="max-width:640px;">
    <h3><?= Helpers::e(t('konto.meine_angaben')) ?></h3>
    <form method="post" action="/konto.php">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="kontakt">
        <div class="feldreihe">
            <div class="feld">
                <label for="echter_name"><?= Helpers::e(t('konto.name')) ?></label>
                <input type="text" id="echter_name" name="echter_name" value="<?= Helpers::e($benutzer['echter_name']) ?>" maxlength="100">
            </div>
            <div class="feld">
                <label for="email"><?= Helpers::e(t('konto.email')) ?></label>
                <input type="email" id="email" name="email" value="<?= Helpers::e($benutzer['email']) ?>">
            </div>
        </div>
        <button type="submit" class="btn"><?= Helpers::e(t('allg.speichern')) ?></button>
    </form>
</div>

<div class="karte" style="max-width:640px;">
    <h3><?= Helpers::e(t('konto.passwort_aendern')) ?></h3>
    <form method="post" action="/konto.php">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="passwort">
        <div class="feld">
            <label for="aktuelles_passwort"><?= Helpers::e(t('konto.aktuelles_passwort')) ?></label>
            <input type="password" id="aktuelles_passwort" name="aktuelles_passwort" required autocomplete="current-password">
        </div>
        <div class="feldreihe">
            <div class="feld">
                <label for="passwort"><?= Helpers::e(t('konto.neues_passwort')) ?></label>
                <input type="password" id="passwort" name="passwort" required minlength="8" autocomplete="new-password">
            </div>
            <div class="feld">
                <label for="passwort_wiederholen"><?= Helpers::e(t('konto.wiederholen')) ?></label>
                <input type="password" id="passwort_wiederholen" name="passwort_wiederholen" required minlength="8" autocomplete="new-password">
            </div>
        </div>
        <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('konto.passwort_btn')) ?></button>
    </form>
</div>

<div class="karte" style="max-width:640px;">
    <h3><?= Helpers::e(t('konto.sprache')) ?></h3>
    <form method="post" action="/konto.php">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="sprache">
        <div class="feld">
            <select name="sprache" id="sprache">
                <?php foreach (\App\I18n::SPRACHEN as $code => $name): ?>
                    <option value="<?= Helpers::e($code) ?>" <?= ($benutzer['sprache'] ?? '') === $code ? 'selected' : '' ?>><?= Helpers::e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn"><?= Helpers::e(t('allg.speichern')) ?></button>
    </form>
</div>
