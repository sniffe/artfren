<?php
/** @var array $gruppen */
/** @var bool $istAdmin */
declare(strict_types=1);

use App\Helpers;
?>
<div class="toolbar">
    <h2><?= Helpers::e(t('gruppen.titel')) ?></h2>
    <?php if ($istAdmin): ?>
        <div class="toolbar-aktionen">
            <a href="/gruppe_neu.php" class="btn btn--primaer"><?= Helpers::e(t('gruppen.neue_gruppe')) ?></a>
        </div>
    <?php endif; ?>
</div>

<table class="liste">
    <thead>
        <tr>
            <th><?= Helpers::e(t('gruppen.name')) ?></th>
            <th><?= Helpers::e(t('gruppen.anzahl_werke')) ?></th>
            <th><?= Helpers::e(t('gruppen.erstellt_am')) ?></th>
            <th><?= Helpers::e(t('gruppen.aktionen')) ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($gruppen as $g): $id = (int) $g['id']; ?>
        <tr>
            <td><a href="/gruppe.php?id=<?= $id ?>" class="gruppenname"><?= Helpers::e($g['name']) ?></a></td>
            <td><?= (int) $g['anzahl_werke'] ?></td>
            <td class="text-klein"><?= Helpers::e(Helpers::formatDatum($g['erstellt_am'], 'd.m.Y')) ?></td>
            <td class="aktionen">
                <?php if ($istAdmin): /* Eingeschränkte Benutzer: Excel/Bilder nur auf der Gruppenseite, sofern freigeschaltet. */ ?>
                <a href="/export_gruppe.php?id=<?= $id ?>&amp;format=xlsx" class="btn btn--klein" title="<?= Helpers::e(t('gruppen.excel_titel')) ?>">Excel</a>
                <a href="/export_gruppe.php?id=<?= $id ?>&amp;format=bilder" class="btn btn--klein" title="<?= Helpers::e(t('gruppen.bilder_titel')) ?>"><?= Helpers::e(t('export.bilder')) ?></a>
                <?php endif; ?>
                <a href="/export_gruppe_pdf.php?id=<?= $id ?>" class="btn btn--klein">PDF</a>
                <?php if ($istAdmin): ?>
                    <details class="aufklappen">
                        <summary class="btn btn--klein"><?= Helpers::e(t('gruppen.umbenennen')) ?></summary>
                        <form method="post" action="/gruppen.php">
                            <?= Helpers::csrfField() ?>
                            <input type="hidden" name="aktion" value="umbenennen">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="text" name="name" value="<?= Helpers::e($g['name']) ?>" maxlength="200" required aria-label="<?= Helpers::e(t('gruppen.neuer_name')) ?>">
                            <button type="submit" class="btn btn--klein btn--primaer"><?= Helpers::e(t('allg.speichern')) ?></button>
                        </form>
                    </details>
                    <form method="post" action="/gruppen.php"
                          data-bestaetigen="<?= Helpers::e(t('gruppen.loeschen_frage', ['name' => $g['name']])) ?>">
                        <?= Helpers::csrfField() ?>
                        <input type="hidden" name="aktion" value="loeschen">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="btn btn--klein btn--gefahr"><?= Helpers::e(t('allg.loeschen')) ?></button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$gruppen): ?>
        <tr><td colspan="4" class="text-sekundaer">
            <?= Helpers::e($istAdmin ? t('gruppen.leer_admin') : t('gruppen.leer_benutzer')) ?>
        </td></tr>
    <?php endif; ?>
    </tbody>
</table>
