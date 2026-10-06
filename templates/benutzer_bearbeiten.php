<?php
/** @var array $ziel */
/** @var string|null $fehler */
/** @var array $gruppen */
/** @var int[] $ausgewaehlt */
declare(strict_types=1);

use App\Helpers;

$zielId = (int) $ziel['id'];
?>
<p class=”text-klein”><a href=”/benutzer.php”><?= Helpers::e(t('benutzer_bearbeiten.zurueck')) ?></a></p>
<h2><?= Helpers::e(t('benutzer_bearbeiten.titel', ['name' => $ziel['benutzername']])) ?></h2>

<?php if ($fehler): ?>
    <div class=”flash flash--fehler”><?= Helpers::e($fehler) ?></div>
<?php endif; ?>

<div class=”karte” style=”max-width:820px;”>
    <h3><?= Helpers::e(t('benutzer_bearbeiten.stammdaten')) ?></h3>
    <form method=”post” action=”/benutzer_bearbeiten.php?id=<?= $zielId ?>”>
        <?= Helpers::csrfField() ?>
        <input type=”hidden” name=”aktion” value=”speichern”>
        <input type=”hidden” name=”id” value=”<?= $zielId ?>”>
        <div class=”feldreihe”>
            <div class=”feld”>
                <label for=”echter_name”><?= Helpers::e(t('benutzer_bearbeiten.echter_name')) ?></label>
                <input type=”text” id=”echter_name” name=”echter_name” value=”<?= Helpers::e($ziel['echter_name']) ?>” maxlength=”100”>
            </div>
            <div class=”feld”>
                <label for=”email”><?= Helpers::e(t('konto.email')) ?></label>
                <input type=”email” id=”email” name=”email” value=”<?= Helpers::e($ziel['email']) ?>”>
            </div>
            <div class=”feld”>
                <label for=”rolle”><?= Helpers::e(t('benutzer.rolle')) ?></label>
                <select id=”rolle” name=”rolle” data-rolle-umschalter=”#gruppen-auswahl-bearbeiten”>
                    <option value=”eingeschraenkt” <?= $ziel['rolle'] !== 'admin' ? 'selected' : '' ?>><?= Helpers::e(t('benutzer_bearbeiten.rolle_eingeschraenkt')) ?></option>
                    <option value=”admin” <?= $ziel['rolle'] === 'admin' ? 'selected' : '' ?>><?= Helpers::e(t('benutzer_bearbeiten.rolle_admin')) ?></option>
                </select>
            </div>
        </div>
        <?php $auswahlId = 'gruppen-auswahl-bearbeiten'; require __DIR__ . '/_gruppen_auswahl.php'; ?>
        <div class=”feld” id=”export-rechte-bearbeiten” <?= $ziel['rolle'] === 'admin' ? 'style=”display:none;”' : '' ?>>
            <label>
                <input type=”checkbox” name=”darf_excel” value=”1” <?= ($ziel['darf_excel'] ?? 0) ? 'checked' : '' ?>>
                <?= Helpers::e(t('benutzer_bearbeiten.darf_excel')) ?>
            </label><br>
            <label>
                <input type=”checkbox” name=”darf_bilder_export” value=”1” <?= ($ziel['darf_bilder_export'] ?? 0) ? 'checked' : '' ?>>
                <?= Helpers::e(t('benutzer_bearbeiten.darf_bilder')) ?>
            </label>
        </div>
        <div class=”feld”>
            <label for=”sprache_benutzer”><?= Helpers::e(t('benutzer_bearbeiten.sprache')) ?></label>
            <select name=”sprache” id=”sprache_benutzer”>
                <option value=””><?= Helpers::e(t('sprache.standard')) ?></option>
                <?php foreach (\App\I18n::SPRACHEN as $code => $name): ?>
                    <option value=”<?= Helpers::e($code) ?>” <?= ($ziel['sprache'] ?? '') === $code ? 'selected' : '' ?>><?= Helpers::e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type=”submit” class=”btn btn--primaer”><?= Helpers::e(t('allg.speichern')) ?></button>
    </form>
</div>

<div class=”karte” style=”max-width:820px;”>
    <h3><?= Helpers::e(t('benutzer_bearbeiten.passwort_setzen')) ?></h3>
    <p class=”text-klein text-sekundaer”><?= Helpers::e(t('benutzer_bearbeiten.passwort_hinweis')) ?></p>
    <form method=”post” action=”/benutzer_bearbeiten.php?id=<?= $zielId ?>” autocomplete=”off”>
        <?= Helpers::csrfField() ?>
        <input type=”hidden” name=”aktion” value=”passwort”>
        <input type=”hidden” name=”id” value=”<?= $zielId ?>”>
        <div class=”feldreihe”>
            <div class=”feld”>
                <label for=”passwort”><?= Helpers::e(t('benutzer_bearbeiten.passwort_neu')) ?></label>
                <input type=”password” id=”passwort” name=”passwort” required minlength=”8” autocomplete=”new-password”>
            </div>
            <div class=”feld”>
                <label for=”passwort_wiederholen”><?= Helpers::e(t('konto.wiederholen')) ?></label>
                <input type=”password” id=”passwort_wiederholen” name=”passwort_wiederholen” required minlength=”8” autocomplete=”new-password”>
            </div>
        </div>
        <button type=”submit” class=”btn”><?= Helpers::e(t('benutzer_bearbeiten.passwort_btn')) ?></button>
    </form>
</div>
