<?php
/** @var array $backups */
/** @var int $bilderGroesse */
/** @var int $bilderAnzahl */
declare(strict_types=1);

use App\Helpers;
?>
<h2>Backup</h2>
<p class="text-sekundaer">Kein automatischer Zeitplan – Backups werden manuell erstellt. Tipp: Backups regelmäßig herunterladen und <strong>zusätzlich außerhalb des Servers</strong> aufbewahren; ein Backup auf demselben Server hilft nicht, wenn der Server ausfällt.</p>

<div class="feldreihe" style="margin-bottom:var(--abstand-l);">
    <form method="post" action="/backup.php" class="karte" style="flex:1;min-width:260px;">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="erstellen">
        <input type="hidden" name="umfang" value="komplett">
        <h3>Komplett</h3>
        <p class="text-klein text-sekundaer">Datenbank und alle <?= $bilderAnzahl ?> Bilder (ca. <?= Helpers::formatGroesse($bilderGroesse) ?>). Kann bei vielen Bildern einige Minuten dauern.</p>
        <button type="submit" class="btn btn--primaer">Komplett-Backup erstellen</button>
    </form>
    <form method="post" action="/backup.php" class="karte" style="flex:1;min-width:260px;">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="aktion" value="erstellen">
        <input type="hidden" name="umfang" value="nur_datenbank">
        <h3>Nur Datenbank</h3>
        <p class="text-klein text-sekundaer">Alle Daten, Gruppen und Benutzer ohne Bilddateien – klein und schnell, ideal nach jedem Import.</p>
        <button type="submit" class="btn">Datenbank-Backup erstellen</button>
    </form>
</div>

<table class="liste">
    <thead>
        <tr>
            <th>Dateiname</th>
            <th>Umfang</th>
            <th>Datum</th>
            <th>Größe</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($backups as $b): ?>
        <tr>
            <td class="text-klein"><?= Helpers::e($b['dateiname']) ?></td>
            <td class="text-klein"><?= (int) $b['mit_bildern'] ? 'komplett' : 'nur Datenbank' ?></td>
            <td class="text-klein"><?= Helpers::e(Helpers::formatDatum($b['erstellt_am'])) ?></td>
            <td class="text-klein"><?= Helpers::formatGroesse((int) $b['dateigroesse']) ?></td>
            <td class="aktionen">
                <a href="/backup_download.php?id=<?= (int) $b['id'] ?>" class="btn btn--klein">Herunterladen</a>
                <form method="post" action="/backup.php" data-bestaetigen="Backup „<?= Helpers::e($b['dateiname']) ?>“ wirklich löschen?">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="aktion" value="loeschen">
                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                    <button type="submit" class="btn btn--klein btn--gefahr">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$backups): ?>
        <tr><td colspan="5" class="text-sekundaer">Noch keine Backups vorhanden.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
