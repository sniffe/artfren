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
<h2>Spalten zuordnen</h2>

<?php if (!empty($info)): ?>
    <p class="text-sekundaer">
        <?= Helpers::e($dateiname ?? '') ?> (<?= Helpers::e($info['format']) ?>):
        Kopfzeile in Zeile <?= (int) $info['kopfzeile'] ?> erkannt, <?= $gesamtzeilen ?> Datenzeilen.
        <?php if ($info['farbige_zeilen'] > 0): ?>
            <?= (int) $info['farbige_zeilen'] ?> farbig markierte Zeilen → Spalte „<?= Helpers::e(TabellenImport::FARB_SPALTE) ?>“.
        <?php endif; ?>
    </p>
<?php endif; ?>
<p class="text-sekundaer">Vorschau der ersten <?= count($zeilen) ?> Zeilen. Die Zuordnung wurde automatisch erkannt – bitte prüfen. Ort, Maler und Titel sind Pflicht.</p>

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
                    <div class="text-klein text-sekundaer"><?= Helpers::e($spalte !== '' ? $spalte : '(ohne Überschrift)') ?></div>
                    <select name="mapping[<?= (int) $i ?>]" aria-label="Zuordnung für Spalte <?= (int) $i + 1 ?>">
                        <?php foreach (TabellenImport::ZIELFELDER as $wert => $label): ?>
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

    <button type="submit" class="btn btn--primaer mt-m">Weiter: Abgleich prüfen</button>
    <a href="/import.php" class="btn mt-m">Abbrechen</a>
</form>
