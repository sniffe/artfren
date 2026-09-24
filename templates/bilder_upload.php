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
        <h3>Ergebnis</h3>
        <p><strong><?= count($ergebnis->gespeichert) ?></strong> gespeichert,
           <strong><?= count($ergebnis->vorhanden) ?></strong> bereits vorhanden (nicht überschrieben),
           <strong><?= count($ergebnis->abgelehnt) ?></strong> abgelehnt.</p>
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
    <h3>Bilder hochladen</h3>
    <p class="text-sekundaer">Der Dateiname muss exakt dem Eintrag in der Spalte „Dateiname“ der Tabelle entsprechen – dann erscheint das Bild automatisch beim richtigen Werk. Viele Bilder am besten als <strong>ZIP-Archiv</strong> hochladen.</p>
    <form method="post" action="/bilder_upload.php" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <div class="feld">
            <label for="dateien">Bilder (<?= Helpers::e(implode(', ', BILD_ENDUNGEN)) ?>) oder ZIP-Archiv</label>
            <input type="file" id="dateien" name="dateien[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.zip,image/*,application/zip" required>
        </div>
        <div class="feld">
            <label style="display:inline-flex;gap:6px;align-items:center;color:var(--farbe-text);">
                <input type="checkbox" name="ueberschreiben" value="1"> Vorhandene Dateien gleichen Namens ersetzen
            </label>
        </div>
        <button type="submit" class="btn btn--primaer">Hochladen</button>
    </form>
    <p class="text-klein text-sekundaer mt-m">Grenzen dieses Servers: höchstens <?= Helpers::e($limits['datei']) ?> je Datei, <?= Helpers::e($limits['gesamt']) ?> je Upload, <?= (int) $limits['anzahl'] ?> Dateien auf einmal. Größere Mengen in mehreren ZIP-Teilen hochladen.</p>
</div>

<div class="karte" style="max-width:720px;">
    <h3>Fehlende Bilder (<?= count($fehlend) ?>)</h3>
    <?php if (!$fehlend): ?>
        <p class="text-sekundaer">Für alle verknüpften Werke ist eine Bilddatei vorhanden.</p>
    <?php else: ?>
        <p class="text-klein text-sekundaer">Diese Dateinamen stehen in der importierten Tabelle, liegen aber noch nicht auf dem Server.</p>
        <table class="liste">
            <thead><tr><th>Dateiname</th><th>Werk</th></tr></thead>
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
