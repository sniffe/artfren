<?php
/** @var \App\BildUpload|null $ergebnis */
/** @var array $fehlend */
/** @var array{datei: string, gesamt: string, anzahl: int} $limits */
declare(strict_types=1);

use App\Helpers;

$aktiverReiter = 'bilder';
require __DIR__ . '/_import_reiter.php';
?>
<?php if ($ergebnis): ?>
    <div class="karte">
        <h3><?= Helpers::e(t('bilder.ergebnis_titel')) ?></h3>
        <p><?= Helpers::e(t('bilder.hochgeladen_info', ['gespeichert' => count($ergebnis->gespeichert), 'vorhanden' => count($ergebnis->vorhanden), 'abgelehnt' => count($ergebnis->abgelehnt)])) ?></p>
        <?php if ($ergebnis->abgelehnt): ?>
            <ul class="text-klein">
                <?php foreach (array_slice($ergebnis->abgelehnt, 0, 100) as [$name, $grund]): ?>
                    <li><?= Helpers::e($name) ?> – <?= Helpers::e($grund) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="karte" style="max-width:720px;">
    <h3><?= Helpers::e(t('bilder.titel')) ?></h3>
    <p class="text-sekundaer"><?= Helpers::e(t('bilder.upload_beschr')) ?></p>
    <form method="post" action="/bilder_upload.php" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <div class="feld">
            <label for="dateien"><?= Helpers::e(t('bilder.dateien_label', ['endungen' => implode(', ', BILD_ENDUNGEN)])) ?></label>
            <input type="file" id="dateien" name="dateien[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.zip,image/*,application/zip" required>
        </div>
        <div class="feld">
            <label style="display:inline-flex;gap:6px;align-items:center;color:var(--farbe-text);">
                <input type="checkbox" name="ueberschreiben" value="1"> <?= Helpers::e(t('bilder.ueberschreiben')) ?>
            </label>
        </div>
        <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('bilder.hochladen_btn')) ?></button>
    </form>
    <p class="text-klein text-sekundaer mt-m"><?= Helpers::e(t('bilder.server_grenzen', ['datei' => $limits['datei'], 'gesamt' => $limits['gesamt'], 'anzahl' => (int) $limits['anzahl']])) ?></p>
</div>

<div class="karte" style="max-width:720px;">
    <h3><?= Helpers::e(t('bilder.fehlend_titel', ['n' => count($fehlend)])) ?></h3>
    <?php if (!$fehlend): ?>
        <p class="text-sekundaer"><?= Helpers::e(t('bilder.alle_vorhanden')) ?></p>
    <?php else: ?>
        <p class="text-klein text-sekundaer"><?= Helpers::e(t('bilder.fehlend_beschr')) ?></p>
        <table class="liste">
            <thead><tr><th><?= Helpers::e(t('bilder.dateiname_spalte')) ?></th><th><?= Helpers::e(t('bilder.werk_spalte')) ?></th></tr></thead>
            <tbody>
            <?php foreach (array_slice($fehlend, 0, 300) as $f): ?>
                <tr>
                    <td class="text-klein"><code><?= Helpers::e($f['dateiname']) ?></code></td>
                    <td class="text-klein"><?= Helpers::e($f['beispiel']) ?><?= (int) $f['anzahl'] > 1 ? ' (+' . ((int) $f['anzahl'] - 1) . ')' : '' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
