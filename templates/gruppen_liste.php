<?php
/** @var array $gruppen */
/** @var bool $istAdmin */
declare(strict_types=1);

use App\Helpers;
?>
<div class="toolbar">
    <h2>Gruppen</h2>
    <?php if ($istAdmin): ?>
        <div class="toolbar-aktionen">
            <a href="/gruppe_neu.php" class="btn btn--primaer">Neue Gruppe anlegen</a>
        </div>
    <?php endif; ?>
</div>

<table class="liste">
    <thead>
        <tr>
            <th>Name</th>
            <th>Anzahl Werke</th>
            <th>Erstellt am</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($gruppen as $g): ?>
        <tr>
            <td>
                <?php if ($istAdmin): ?>
                    <form method="post" action="/gruppen.php" style="display:flex;gap:6px;align-items:center;">
                        <?= Helpers::csrfField() ?>
                        <input type="hidden" name="aktion" value="umbenennen">
                        <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                        <a href="/gruppe.php?id=<?= (int) $g['id'] ?>" style="font-family:var(--schrift-serif);font-style:italic;white-space:nowrap;"><?= Helpers::e($g['name']) ?></a>
                        <input type="text" name="name" value="<?= Helpers::e($g['name']) ?>" style="max-width:220px;">
                        <button type="submit" class="btn btn--klein">Speichern</button>
                    </form>
                <?php else: ?>
                    <a href="/gruppe.php?id=<?= (int) $g['id'] ?>" style="font-family:var(--schrift-serif);font-style:italic;"><?= Helpers::e($g['name']) ?></a>
                <?php endif; ?>
            </td>
            <td><?= (int) $g['anzahl_werke'] ?></td>
            <td class="text-klein"><?= Helpers::e(Helpers::formatDatum($g['erstellt_am'], 'd.m.Y')) ?></td>
            <td class="aktionen">
                <a href="/gruppe.php?id=<?= (int) $g['id'] ?>" class="btn btn--klein">Ansehen</a>
                <a href="/export_gruppe_zip.php?id=<?= (int) $g['id'] ?>" class="btn btn--klein">ZIP</a>
                <a href="/export_gruppe_pdf.php?id=<?= (int) $g['id'] ?>" class="btn btn--klein">PDF</a>
                <?php if ($istAdmin): ?>
                    <form method="post" action="/gruppen.php" onsubmit="return confirm('Gruppe „<?= Helpers::e($g['name']) ?>“ wirklich löschen? Die Kunstwerke selbst bleiben erhalten.');">
                        <?= Helpers::csrfField() ?>
                        <input type="hidden" name="aktion" value="loeschen">
                        <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                        <button type="submit" class="btn btn--klein btn--gefahr">Löschen</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$gruppen): ?>
        <tr><td colspan="4" class="text-sekundaer">Noch keine Gruppen vorhanden.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
