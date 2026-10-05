<?php
/** @var array $werke */
declare(strict_types=1);

use App\Helpers;
?>
<div class="toolbar">
    <h2 style="margin:0;">Papierkorb</h2>
</div>
<p class="text-sekundaer">Gelöschte Werke. Wiederherstellen oder endgültig löschen. Endgültig gelöschte Werke werden beim Import nicht mehr neu angelegt.</p>

<?php if (!$werke): ?>
    <p class="text-sekundaer mt-m">Der Papierkorb ist leer.</p>
<?php else: ?>
<table class="liste mt-m">
    <thead>
        <tr>
            <th>Bild</th>
            <th>Ort</th>
            <th>Maler</th>
            <th>Titel</th>
            <th>Gelöscht am</th>
            <th>Gelöscht von</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($werke as $w):
        $thumb = Helpers::bildUrl($w['bild_id'], $w['bild_dateiname'], 't'); ?>
        <tr>
            <td data-label="Bild">
                <?php if ($thumb): ?>
                    <img class="thumb" src="<?= Helpers::e($thumb) ?>" alt="" loading="lazy">
                <?php endif; ?>
            </td>
            <td data-label="Ort" class="text-klein"><?= Helpers::e($w['ort'] ?? '') ?></td>
            <td data-label="Maler" class="text-klein"><?= Helpers::e($w['maler'] ?? '') ?></td>
            <td data-label="Titel" class="text-klein"><?= Helpers::e($w['titel'] ?? '') ?></td>
            <td data-label="Gelöscht am" class="text-klein"><?= Helpers::e(Helpers::formatDatum($w['geloescht_am'] ?? '')) ?></td>
            <td data-label="Gelöscht von" class="text-klein"><?= Helpers::e($w['geloescht_von'] ?? '') ?></td>
            <td data-label="" style="white-space:nowrap;">
                <form method="post" action="/papierkorb.php" style="display:inline;">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="aktion" value="wiederherstellen">
                    <input type="hidden" name="werk_id" value="<?= (int) $w['id'] ?>">
                    <button type="submit" class="btn btn--klein">Wiederherstellen</button>
                </form>
                <form method="post" action="/papierkorb.php" style="display:inline;"
                      data-bestaetigen="Werk „<?= Helpers::e($w['titel'] ?? 'Unbenannt') ?>" endgültig löschen? Diese Aktion kann nicht rückgängig gemacht werden.">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="aktion" value="endgueltig_loeschen">
                    <input type="hidden" name="werk_id" value="<?= (int) $w['id'] ?>">
                    <button type="submit" class="btn btn--klein btn--gefahr"><?= Helpers::icon('papierkorb') ?> Endgültig löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
