<?php
/** @var string[] $header */
/** @var array $zeilen */
/** @var int $gesamtzeilen */
/** @var array<int,string> $mapping */
/** @var string $tmp_datei */
/** @var string|null $fehler */
/** @var array|null $info */
/** @var string|null $dateiname */
declare(strict_types=1);

use App\Helpers;
use App\TabellenImport;

$aktiverReiter = 'tabelle';
require __DIR__ . '/_import_reiter.php';
?>
<h2><?= Helpers::e(t('import.spalten_zuordnen')) ?></h2>

<?php if (!empty($info)): ?>
    <p class=”text-sekundaer”>
        <?= Helpers::e(t('import.datei_info', ['name' => $dateiname ?? '', 'format' => $info['format'], 'zeile' => (int) $info['kopfzeile'], 'daten' => $gesamtzeilen])) ?>
        <?php if ($info['farbige_zeilen'] > 0): ?>
            <?= Helpers::e(t('import.farbige_zeilen', ['n' => (int) $info['farbige_zeilen'], 'spalte' => TabellenImport::FARB_SPALTE])) ?>
        <?php endif; ?>
    </p>
<?php endif; ?>
<p class=”text-sekundaer”><?= Helpers::e(t('import.vorschau', ['n' => count($zeilen)])) ?></p>

<?php if ($fehler): ?>
    <div class="flash flash--fehler"><?= Helpers::e($fehler) ?></div>
<?php endif; ?>

<form method="post" action="/import.php">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="aktion" value="abgleich">
    <input type="hidden" name="tmp_datei" value="<?= Helpers::e($tmp_datei) ?>">

    <div style="overflow-x:auto;">
    <table class="liste">
        <thead>
            <tr>
                <?php foreach ($header as $i => $spalte): ?>
                <th>
                    <div class="text-klein text-sekundaer"><?= Helpers::e($spalte !== '' ? $spalte : t('import.ohne_ueberschrift')) ?></div>
                    <select name="mapping[<?= (int) $i ?>]" aria-label="<?= Helpers::e(t('import.zuordnung_aria', ['n' => (int) $i + 1])) ?>">
                        <?php foreach (TabellenImport::zielfelder() as $wert => $label): ?>
                            <option value="<?= Helpers::e($wert) ?>" <?= ($mapping[$i] ?? '') === $wert ? 'selected' : '' ?>><?= Helpers::e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($zeilen as $zeile): ?>
            <tr>
                <?php foreach ($header as $i => $spalte): ?>
                    <td class="text-klein"><?= Helpers::e((string) ($zeile[$i] ?? '')) ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <button type="submit" class="btn btn--primaer mt-m"><?= Helpers::e(t('import.weiter_abgleich')) ?></button>
    <a href="/import.php" class="btn mt-m"><?= Helpers::e(t('allg.abbrechen')) ?></a>
</form>
