<?php
/** @var string[] $header */
/** @var array $zeilen */
/** @var int $gesamtzeilen */
/** @var string $trennzeichen */
/** @var array<int,string> $mapping */
/** @var string $tmp_datei */
declare(strict_types=1);

use App\CsvImport;
use App\Helpers;
?>
<h2>CSV-Import – Spaltenzuordnung prüfen</h2>
<p class="text-sekundaer">Vorschau der ersten <?= count($zeilen) ?> von <?= $gesamtzeilen ?> Datenzeilen. Die Spaltenzuordnung wurde automatisch erkannt – bitte prüfen und bei Bedarf korrigieren.</p>

<form method="post" action="/import.php">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="aktion" value="abgleich">
    <input type="hidden" name="tmp_datei" value="<?= Helpers::e($tmp_datei) ?>">
    <input type="hidden" name="trennzeichen" value="<?= Helpers::e($trennzeichen) ?>">

    <div style="overflow-x:auto;">
    <table class="liste">
        <thead>
            <tr>
                <?php foreach ($header as $i => $spalte): ?>
                <th>
                    <div class="text-klein text-sekundaer"><?= Helpers::e($spalte ?: '(ohne Titel)') ?></div>
                    <select name="mapping[<?= $i ?>]">
                        <?php foreach (CsvImport::ZIELFELDER as $wert => $label): ?>
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
