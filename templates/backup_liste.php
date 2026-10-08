<?php
/** @var array $backups */
/** @var int $bilderGroesse */
/** @var int $bilderAnzahl */
declare(strict_types=1);

use App\Helpers;
?>
<h2><?= Helpers::e(t('backup.titel')) ?></h2>
<p class="text-sekundaer"><?= Helpers::e(t('backup.hinweis')) ?></p>

<div class="feldreihe" style="margin-bottom:var(--abstand-l);">
    <form method="post" action="/backup.php" class="karte" style="flex:1;min-width:260px;">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="erstellen">
        <input type="hidden" name="umfang" value="komplett">
        <h3><?= Helpers::e(t('backup.komplett_titel')) ?></h3>
        <p class="text-klein text-sekundaer"><?= Helpers::e(t('backup.komplett_beschreibung', ['n' => $bilderAnzahl, 'groesse' => Helpers::formatGroesse($bilderGroesse)])) ?></p>
        <button type="submit" class="btn btn--primaer"><?= Helpers::e(t('backup.komplett_btn')) ?></button>
    </form>
    <form method="post" action="/backup.php" class="karte" style="flex:1;min-width:260px;">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="erstellen">
        <input type="hidden" name="umfang" value="nur_datenbank">
        <h3><?= Helpers::e(t('backup.db_titel')) ?></h3>
        <p class="text-klein text-sekundaer"><?= Helpers::e(t('backup.db_beschreibung')) ?></p>
        <button type="submit" class="btn"><?= Helpers::e(t('backup.db_btn')) ?></button>
    </form>
</div>

<table class="liste">
    <thead>
        <tr>
            <th><?= Helpers::e(t('backup.dateiname')) ?></th>
            <th><?= Helpers::e(t('backup.umfang')) ?></th>
            <th><?= Helpers::e(t('backup.datum')) ?></th>
            <th><?= Helpers::e(t('backup.groesse')) ?></th>
            <th><?= Helpers::e(t('backup.aktionen')) ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($backups as $b): ?>
        <tr>
            <td class="text-klein"><?= Helpers::e($b['dateiname']) ?></td>
            <td class="text-klein"><?= (int) $b['mit_bildern'] ? Helpers::e(t('backup.komplett_label')) : Helpers::e(t('backup.nur_db_label')) ?></td>
            <td class="text-klein"><?= Helpers::e(Helpers::formatDatum($b['erstellt_am'])) ?></td>
            <td class="text-klein"><?= Helpers::formatGroesse((int) $b['dateigroesse']) ?></td>
            <td class="aktionen">
                <a href="/backup_download.php?id=<?= (int) $b['id'] ?>" class="btn btn--klein"><?= Helpers::e(t('backup.herunterladen')) ?></a>
                <form method="post" action="/backup.php" data-bestaetigen="<?= Helpers::e(t('backup.loeschen_frage', ['name' => $b['dateiname']])) ?>">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="aktion" value="loeschen">
                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                    <button type="submit" class="btn btn--klein btn--gefahr"><?= Helpers::e(t('allg.loeschen')) ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$backups): ?>
        <tr><td colspan="5" class="text-sekundaer"><?= Helpers::e(t('backup.leer')) ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
