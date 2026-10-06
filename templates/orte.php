<?php
/** @var array<string, int> $orte */
/** @var array<int, array<string, int>> $vorschlaege */
/** @var array<string, string> $aliase */
declare(strict_types=1);

use App\Helpers;
use App\I18n;

$aktiverReiter = 'orte';
require __DIR__ . '/_import_reiter.php';
?>
<h2><?= Helpers::e(t('orte.titel')) ?></h2>
<p class="text-sekundaer"><?= Helpers::e(t('orte.beschreibung')) ?></p>

<?php if ($vorschlaege): ?>
    <h3><?= Helpers::e(t('orte.vorschlaege_titel', ['n' => count($vorschlaege)])) ?></h3>
    <div class="feldreihe">
        <?php foreach ($vorschlaege as $i => $gruppe): ?>
            <form method="post" action="/orte.php" class="karte" style="flex:1;min-width:280px;"
                  data-bestaetigen="<?= Helpers::e(t('orte.zsmf_bestaetigen')) ?>">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="zusammenfuehren">
                <p class="text-klein text-sekundaer"><?= Helpers::e(t('orte.richtige_schreibweise')) ?></p>
                <?php $erster = true; foreach ($gruppe as $ort => $anzahl): ?>
                    <input type="hidden" name="quellen[]" value="<?= Helpers::e($ort) ?>">
                    <label style="display:flex;gap:6px;align-items:center;color:var(--farbe-text);font-size:var(--schrift-groesse-basis);">
                        <input type="radio" name="ziel" value="<?= Helpers::e($ort) ?>" <?= $erster ? 'checked' : '' ?>>
                        <?= Helpers::e($ort) ?> <span class="text-klein text-sekundaer">(<?= Helpers::e(I18n::plural($anzahl, 'gruppe.werke_zaehler', ['n' => $anzahl])) ?>)</span>
                    </label>
                <?php $erster = false; endforeach; ?>
                <button type="submit" class="btn btn--primaer btn--klein mt-m"><?= Helpers::e(t('orte.zusammenfuehren')) ?></button>
            </form>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-sekundaer"><?= Helpers::e(t('orte.keine_aehnliche')) ?></p>
<?php endif; ?>

<h3 class="mt-l"><?= Helpers::e(t('orte.alle_orte', ['n' => count($orte)])) ?></h3>
<form method="post" action="/orte.php" class="karte" data-bestaetigen="<?= Helpers::e(t('orte.manuell_bestaetigen')) ?>">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="aktion" value="zusammenfuehren">
    <p class="text-klein text-sekundaer"><?= Helpers::e(t('orte.manuell_beschr')) ?></p>
    <div class="checkliste">
        <?php foreach ($orte as $ort => $anzahl): ?>
            <label><input type="checkbox" name="quellen[]" value="<?= Helpers::e($ort) ?>"> <?= Helpers::e($ort) ?> <span class="text-klein text-sekundaer">(<?= $anzahl ?>)</span></label>
        <?php endforeach; ?>
    </div>
    <div class="feldreihe mt-m" style="align-items:flex-end;">
        <div class="feld" style="max-width:360px;margin-bottom:0;">
            <label for="ziel_neu"><?= Helpers::e(t('orte.neuer_name')) ?></label>
            <input type="text" id="ziel_neu" name="ziel_neu" maxlength="200" required>
        </div>
        <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('orte.ausgewaehlte_zsmf')) ?></button>
    </div>
</form>

<?php if ($aliase): ?>
    <h3 class="mt-l"><?= Helpers::e(t('orte.gemerkte_schreibweisen')) ?></h3>
    <table class="liste" style="max-width:720px;">
        <thead><tr><th><?= Helpers::e(t('orte.schreibweise_spalte')) ?></th><th><?= Helpers::e(t('orte.wird_zu')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($aliase as $alias => $ziel): ?>
            <tr>
                <td><?= Helpers::e($alias) ?></td>
                <td><?= Helpers::e($ziel) ?></td>
                <td>
                    <form method="post" action="/orte.php">
                        <?= Helpers::csrfField() ?>
                        <input type="hidden" name="aktion" value="alias_loeschen">
                        <input type="hidden" name="alias" value="<?= Helpers::e($alias) ?>">
                        <button type="submit" class="btn btn--klein"><?= Helpers::e(t('orte.entfernen')) ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
