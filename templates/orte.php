<?php
/** @var array<string, int> $orte */
/** @var array<int, array<string, int>> $vorschlaege */
/** @var array<string, string> $aliase */
declare(strict_types=1);

use App\Helpers;

$aktiverReiter = 'orte';
require __DIR__ . '/_import_reiter.php';
?>
<h2>Orte bereinigen</h2>
<p class="text-sekundaer">Tippvarianten zersplittern den Orts-Filter. Beim Zusammenführen merkt sich das System die alten Schreibweisen: ein späterer Import der unveränderten Excel-Datei ordnet sie automatisch dem richtigen Ort zu.</p>

<?php if ($vorschlaege): ?>
    <h3>Vorschläge (<?= count($vorschlaege) ?>)</h3>
    <div class="feldreihe">
        <?php foreach ($vorschlaege as $i => $gruppe): ?>
            <form method="post" action="/orte.php" class="karte" style="flex:1;min-width:280px;"
                  data-bestaetigen="Die ausgewählten Schreibweisen zusammenführen?">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="aktion" value="zusammenfuehren">
                <p class="text-klein text-sekundaer">Richtige Schreibweise wählen:</p>
                <?php $erster = true; foreach ($gruppe as $ort => $anzahl): ?>
                    <input type="hidden" name="quellen[]" value="<?= Helpers::e($ort) ?>">
                    <label style="display:flex;gap:6px;align-items:center;color:var(--farbe-text);font-size:var(--schrift-groesse-basis);">
                        <input type="radio" name="ziel" value="<?= Helpers::e($ort) ?>" <?= $erster ? 'checked' : '' ?>>
                        <?= Helpers::e($ort) ?> <span class="text-klein text-sekundaer">(<?= $anzahl ?> <?= $anzahl === 1 ? 'Werk' : 'Werke' ?>)</span>
                    </label>
                <?php $erster = false; endforeach; ?>
                <button type="submit" class="btn btn--primaer btn--klein mt-m">Zusammenführen</button>
            </form>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-sekundaer">Keine ähnlichen Schreibweisen gefunden.</p>
<?php endif; ?>

<h3 class="mt-l">Alle Orte (<?= count($orte) ?>)</h3>
<form method="post" action="/orte.php" class="karte" data-bestaetigen="Die ausgewählten Orte zusammenführen bzw. umbenennen?">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="aktion" value="zusammenfuehren">
    <p class="text-klein text-sekundaer">Einen oder mehrere Orte anhaken und einen gemeinsamen neuen Namen eingeben – auch zum einfachen Umbenennen.</p>
    <div class="checkliste">
        <?php foreach ($orte as $ort => $anzahl): ?>
            <label><input type="checkbox" name="quellen[]" value="<?= Helpers::e($ort) ?>"> <?= Helpers::e($ort) ?> <span class="text-klein text-sekundaer">(<?= $anzahl ?>)</span></label>
        <?php endforeach; ?>
    </div>
    <div class="feldreihe mt-m" style="align-items:flex-end;">
        <div class="feld" style="max-width:360px;margin-bottom:0;">
            <label for="ziel_neu">Neuer Name</label>
            <input type="text" id="ziel_neu" name="ziel_neu" maxlength="200" required>
        </div>
        <button type="submit" class="btn btn--primaer">Ausgewählte zusammenführen</button>
    </div>
</form>

<?php if ($aliase): ?>
    <h3 class="mt-l">Gemerkte Schreibweisen</h3>
    <table class="liste" style="max-width:720px;">
        <thead><tr><th>Schreibweise in der Tabelle</th><th>wird zu</th><th></th></tr></thead>
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
                        <button type="submit" class="btn btn--klein">Entfernen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
