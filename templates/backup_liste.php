<?php
/** @var array $backups */
declare(strict_types=1);

use App\Helpers;

function formatiere_groesse(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 1, ',', '.') . ' MB';
    }
    return number_format($bytes / 1024, 0, ',', '.') . ' KB';
}
?>
<h2>Backup</h2>
<p class="text-sekundaer">Sichert die Datenbank und alle Bilddateien als ZIP. Kein automatischer Zeitplan – Backups werden manuell erstellt und verwaltet.</p>

<form method="post" action="/backup.php" style="margin-bottom:var(--abstand-l);">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="aktion" value="erstellen">
    <button type="submit" class="btn btn--primaer">Backup jetzt erstellen</button>
</form>

<table class="liste">
    <thead>
        <tr>
            <th>Dateiname</th>
            <th>Datum</th>
            <th>Größe</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($backups as $b): ?>
        <tr>
            <td><?= Helpers::e($b['dateiname']) ?></td>
            <td class="text-klein"><?= Helpers::e(Helpers::formatDatum($b['erstellt_am'])) ?></td>
            <td class="text-klein"><?= formatiere_groesse((int) $b['dateigroesse']) ?></td>
            <td class="aktionen">
                <a href="/backup_download.php?id=<?= (int) $b['id'] ?>" class="btn btn--klein">Herunterladen</a>
                <form method="post" action="/backup.php" onsubmit="return confirm('Backup „<?= Helpers::e($b['dateiname']) ?>“ wirklich löschen?');">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="aktion" value="loeschen">
                    <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                    <button type="submit" class="btn btn--klein btn--gefahr">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$backups): ?>
        <tr><td colspan="4" class="text-sekundaer">Noch keine Backups vorhanden.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
