<?php
/** @var string $getAktion */
/** @var array $profile */
/** @var array|null $profil */
/** @var string[] $standardFelder */
/** @var string $aktiverReiter */
declare(strict_types=1);

use App\ExportProfile;
use App\Felder;
use App\Helpers;
use App\I18n;

require __DIR__ . '/_export_reiter.php';

// ── Liste ─────────────────────────────────────────────────────────────────────
if ($getAktion !== 'neu' && $getAktion !== 'bearbeiten' && $getAktion !== 'ansehen') {
?>
<div class="toolbar">
    <h2 style="margin:0;"><?= Helpers::e(t('profil.titel')) ?></h2>
    <a href="/export_profile.php?aktion=neu" class="btn btn--primaer"><?= Helpers::e(t('profil.neu')) ?></a>
</div>

<?php if (!$profile): ?>
<div class="karte mt-m">
    <p class="text-sekundaer"><?= Helpers::e(t('profil.leer')) ?></p>
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('profil.leer_hinweis')) ?></p>
</div>
<?php else: ?>
<table class="liste mt-m">
    <thead>
        <tr>
            <th><?= Helpers::e(t('profil.name')) ?></th>
            <th><?= Helpers::e(t('profil.felder_anzahl')) ?></th>
            <th><?= Helpers::e(t('profil.bild')) ?></th>
            <th><?= Helpers::e(t('profil.layout_label')) ?></th>
            <th><?= Helpers::e(t('profil.sprache')) ?></th>
            <th><?= Helpers::e(t('profil.erstellt')) ?></th>
            <th><?= Helpers::e(t('profil.aktionen')) ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($profile as $p): ?>
        <?php
        $felder = ExportProfile::felderAusProfil($p);
        $sensibel = ExportProfile::hatSensibleFelder($p);
        ?>
        <tr>
            <td>
                <?= Helpers::e($p['name']) ?>
                <?php if ($p['ist_standard']): ?>
                    <span class="badge badge--admin"><?= Helpers::e(t('profil.standard')) ?></span>
                <?php endif; ?>
                <?php if ($p['fuer_eingeschraenkte']): ?>
                    <span class="badge"><?= Helpers::icon('benutzer') ?></span>
                <?php endif; ?>
                <?php if ($sensibel): ?>
                    <span class="text-sekundaer text-klein"> · <?= Helpers::e(t('profil.sensibel_hinweis')) ?></span>
                <?php endif; ?>
            </td>
            <td><?= count($felder) ?></td>
            <td><?= $p['bild'] ? '✓' : '–' ?></td>
            <td><?= Helpers::e(t('profil.layout_' . $p['layout'])) ?></td>
            <td><?= Helpers::e($p['sprache'] ? (I18n::SPRACHEN[$p['sprache']] ?? $p['sprache']) : t('profil.sprache_exporter')) ?></td>
            <td class="text-klein text-sekundaer"><?= Helpers::e(Helpers::formatDatum($p['geaendert_am'] ?: $p['erstellt_am'], 'd.m.Y')) ?></td>
            <td class="aktionen">
                <a href="/export_profile.php?aktion=ansehen&amp;id=<?= (int) $p['id'] ?>" class="btn btn--klein"><?= Helpers::e(t('profil.ansehen')) ?></a>
                <a href="/export_profile.php?aktion=bearbeiten&amp;id=<?= (int) $p['id'] ?>" class="btn btn--klein"><?= Helpers::e(t('allg.bearbeiten')) ?></a>
                <form method="post" style="display:inline;">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="aktion" value="duplizieren">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" class="btn btn--klein"><?= Helpers::e(t('profil.duplizieren')) ?></button>
                </form>
                <form method="post" style="display:inline;">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="aktion" value="loeschen">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" class="btn btn--klein btn--gefahr"
                            data-bestaetigen="<?= Helpers::e(t('profil.loeschen_frage', ['name' => $p['name']])) ?>"><?= Helpers::e(t('allg.loeschen')) ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
<?php
    return;
}

// ── Ansehen (read-only) ────────────────────────────────────────────────────────
if ($getAktion === 'ansehen' && $profil !== null):
    $felder = ExportProfile::felderAusProfil($profil);
    $bekannte = array_column(Felder::alle(), null, 'key');
?>
<p class="text-klein text-sekundaer"><a href="/export_profile.php"><?= Helpers::e(t('profil.titel')) ?></a></p>
<h2><?= Helpers::e($profil['name']) ?></h2>

<div class="karte" style="max-width:700px;">
    <?php
    $aktuelleGruppe = null;
    foreach (Felder::alle() as $f) {
        if (!in_array($f['key'], $felder, true)) continue;
        if ($f['gruppe'] !== $aktuelleGruppe) {
            if ($aktuelleGruppe !== null) echo '</ul>';
            echo '<h4 style="margin:var(--abstand-m) 0 var(--abstand-s);">' . Helpers::e(t('feldgruppe.' . $f['gruppe'])) . '</h4><ul class="text-klein">';
            $aktuelleGruppe = $f['gruppe'];
        }
        echo '<li>' . Helpers::e(t($f['label'])) . ($f['sensibel'] ? ' <span class="text-sekundaer">(' . Helpers::e(t('profil.sensibel_hinweis')) . ')</span>' : '') . '</li>';
    }
    if ($aktuelleGruppe !== null) echo '</ul>';
    // Felder die nicht mehr in der Registry sind
    $bekannteKeys = array_column(Felder::alle(), 'key', 'key');
    foreach ($felder as $key) {
        if (!isset($bekannteKeys[$key])) {
            echo '<p class="text-klein text-sekundaer">' . Helpers::e($key . ': ' . t('profil.nicht_mehr_vorhanden')) . '</p>';
        }
    }
    ?>
    <dl style="margin-top:var(--abstand-m);">
        <dt class="text-sekundaer text-klein"><?= Helpers::e(t('profil.bild_anzeigen')) ?></dt>
        <dd><?= $profil['bild'] ? t('allg.ja') : t('allg.nein') ?></dd>
        <dt class="text-sekundaer text-klein"><?= Helpers::e(t('profil.titelblock_anzeigen')) ?></dt>
        <dd><?= $profil['titelblock'] ? t('allg.ja') : t('allg.nein') ?></dd>
        <dt class="text-sekundaer text-klein"><?= Helpers::e(t('profil.leer_ausblenden')) ?></dt>
        <dd><?= $profil['leer_ausblenden'] ? t('allg.ja') : t('allg.nein') ?></dd>
        <dt class="text-sekundaer text-klein"><?= Helpers::e(t('profil.layout_label')) ?></dt>
        <dd><?= Helpers::e(t('profil.layout_' . $profil['layout'])) ?></dd>
        <dt class="text-sekundaer text-klein"><?= Helpers::e(t('profil.sprache')) ?></dt>
        <dd><?= Helpers::e($profil['sprache'] ? (I18n::SPRACHEN[$profil['sprache']] ?? $profil['sprache']) : t('profil.sprache_exporter')) ?></dd>
    </dl>
</div>
<div class="toolbar-aktionen mt-m">
    <a href="/export_profile.php?aktion=bearbeiten&amp;id=<?= (int) $profil['id'] ?>" class="btn btn--primaer"><?= Helpers::e(t('allg.bearbeiten')) ?></a>
    <a href="/export_profile.php" class="btn"><?= Helpers::e(t('allg.zurueck')) ?></a>
</div>
<?php
    return;
endif;

// ── Formular (neu / bearbeiten) ────────────────────────────────────────────────
$istNeu   = $profil === null;
$feldKeys = $istNeu ? $standardFelder : ExportProfile::felderAusProfil($profil);
?>
<p class="text-klein text-sekundaer"><a href="/export_profile.php"><?= Helpers::e(t('profil.titel')) ?></a></p>
<h2><?= Helpers::e($istNeu ? t('profil.neu') : $profil['name']) ?></h2>

<form method="post" action="/export_profile.php">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="aktion" value="speichern">
    <?php if (!$istNeu): ?><input type="hidden" name="id" value="<?= (int) $profil['id'] ?>"><?php endif; ?>

    <div class="feld" style="max-width:400px;">
        <label for="profil-name"><?= Helpers::e(t('profil.name')) ?></label>
        <input type="text" id="profil-name" name="name" maxlength="80" required
               value="<?= Helpers::e($profil['name'] ?? '') ?>">
    </div>

    <?php
    $alleFelder = Felder::alle();
    $gruppen = array_unique(array_column($alleFelder, 'gruppe'));
    ?>
    <h3 class="mt-m"><?= Helpers::e(t('pdf.felder_titel')) ?></h3>
    <?php foreach ($gruppen as $gruppenName): ?>
    <fieldset class="karte" style="border:1px solid var(--farbe-linie); margin-bottom:var(--abstand-m); max-width:700px;">
        <legend style="padding:0 var(--abstand-s); font-weight:600;"><?= Helpers::e(t('feldgruppe.' . $gruppenName)) ?></legend>
        <div class="checkliste">
        <?php foreach ($alleFelder as $f):
            if ($f['gruppe'] !== $gruppenName) continue;
        ?>
            <label>
                <input type="checkbox" name="felder[]" value="<?= Helpers::e($f['key']) ?>"
                       <?= in_array($f['key'], $feldKeys, true) ? 'checked' : '' ?>>
                <?= Helpers::e(t($f['label'])) ?>
                <?php if ($f['sensibel']): ?>
                    <span class="text-sekundaer text-klein"> (<?= Helpers::e(t('profil.sensibel_hinweis')) ?>)</span>
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
        </div>
    </fieldset>
    <?php endforeach; ?>

    <div class="karte" style="max-width:700px; margin-bottom:var(--abstand-m);">
        <h3><?= Helpers::e(t('profil.bild_anzeigen')) ?> / <?= Helpers::e(t('profil.titelblock_anzeigen')) ?></h3>
        <div class="checkliste">
            <label>
                <input type="checkbox" name="bild" value="1"
                       <?= ($profil['bild'] ?? 1) ? 'checked' : '' ?>>
                <?= Helpers::e(t('profil.bild_anzeigen')) ?>
            </label>
            <label>
                <input type="checkbox" name="titelblock" value="1"
                       <?= ($profil['titelblock'] ?? 1) ? 'checked' : '' ?>>
                <?= Helpers::e(t('profil.titelblock_anzeigen')) ?>
            </label>
            <label>
                <input type="checkbox" name="leer_ausblenden" value="1"
                       <?= ($profil['leer_ausblenden'] ?? 1) ? 'checked' : '' ?>>
                <?= Helpers::e(t('profil.leer_ausblenden')) ?>
            </label>
            <label>
                <input type="checkbox" name="summe" value="1"
                       <?= ($profil['summe'] ?? 1) ? 'checked' : '' ?>>
                <?= Helpers::e(t('profil.summe_anzeigen')) ?>
            </label>
        </div>
    </div>

    <div class="feldreihe" style="max-width:700px; margin-bottom:var(--abstand-m);">
        <div class="feld">
            <label for="profil-layout"><?= Helpers::e(t('profil.layout_label')) ?></label>
            <select id="profil-layout" name="layout">
                <option value="einzelblatt" <?= ($profil['layout'] ?? 'einzelblatt') === 'einzelblatt' ? 'selected' : '' ?>><?= Helpers::e(t('profil.layout_einzelblatt')) ?></option>
                <option value="liste" <?= ($profil['layout'] ?? '') === 'liste' ? 'selected' : '' ?>><?= Helpers::e(t('profil.layout_liste')) ?></option>
            </select>
        </div>
        <div class="feld">
            <label for="profil-sprache"><?= Helpers::e(t('profil.sprache')) ?></label>
            <select id="profil-sprache" name="sprache">
                <option value=""><?= Helpers::e(t('profil.sprache_exporter')) ?></option>
                <?php foreach (I18n::SPRACHEN as $code => $name): ?>
                    <option value="<?= Helpers::e($code) ?>" <?= ($profil['sprache'] ?? '') === $code ? 'selected' : '' ?>><?= Helpers::e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="karte" style="max-width:700px; margin-bottom:var(--abstand-m);">
        <div class="checkliste">
            <label>
                <input type="checkbox" name="ist_standard" value="1"
                       <?= !empty($profil['ist_standard']) ? 'checked' : '' ?>>
                <?= Helpers::e(t('profil.als_standard')) ?>
            </label>
            <label>
                <input type="checkbox" name="fuer_eingeschraenkte" value="1"
                       <?= !empty($profil['fuer_eingeschraenkte']) ? 'checked' : '' ?>>
                <?= Helpers::e(t('profil.fuer_eingeschraenkte')) ?>
                <span class="text-sekundaer text-klein"> – <?= Helpers::e(t('profil.fuer_eingeschr_warnung')) ?></span>
            </label>
        </div>
    </div>

    <div class="toolbar-aktionen">
        <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('profil.speichern_als')) ?></button>
        <a href="/export_profile.php" class="btn"><?= Helpers::e(t('allg.abbrechen')) ?></a>
    </div>
</form>
