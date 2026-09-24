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
    <?php foreach ($gruppen as $g): $id = (int) $g['id']; ?>
        <tr>
            <td><a href="/gruppe.php?id=<?= $id ?>" class="gruppenname"><?= Helpers::e($g['name']) ?></a></td>
            <td><?= (int) $g['anzahl_werke'] ?></td>
            <td class="text-klein"><?= Helpers::e(Helpers::formatDatum($g['erstellt_am'], 'd.m.Y')) ?></td>
            <td class="aktionen">
                <a href="/export_gruppe_zip.php?id=<?= $id ?>" class="btn btn--klein">ZIP</a>
                <a href="/export_gruppe_pdf.php?id=<?= $id ?>" class="btn btn--klein">PDF</a>
                <?php if ($istAdmin): ?>
                    <details class="aufklappen">
                        <summary class="btn btn--klein">Umbenennen</summary>
                        <form method="post" action="/gruppen.php">
                            <?= Helpers::csrfField() ?>
                            <input type="hidden" name="aktion" value="umbenennen">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="text" name="name" value="<?= Helpers::e($g['name']) ?>" maxlength="200" required aria-label="Neuer Name">
                            <button type="submit" class="btn btn--klein btn--primaer">Speichern</button>
                        </form>
                    </details>
                    <form method="post" action="/gruppen.php"
                          data-bestaetigen="Gruppe „<?= Helpers::e($g['name']) ?>“ wirklich löschen? Die Kunstwerke selbst bleiben erhalten.">
                        <?= Helpers::csrfField() ?>
                        <input type="hidden" name="aktion" value="loeschen">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="btn btn--klein btn--gefahr">Löschen</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$gruppen): ?>
        <tr><td colspan="4" class="text-sekundaer">
            <?= $istAdmin ? 'Noch keine Gruppen vorhanden.' : 'Dir sind noch keine Gruppen zugewiesen. Bitte beim Administrator nachfragen.' ?>
        </td></tr>
    <?php endif; ?>
    </tbody>
</table>
