<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;

$aktuellerBenutzer = Auth::requireAdmin();
$pdo = Database::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'erstellen') {
        $zeitstempel = date('Y-m-d_Hi');
        $dateiname = "backup_{$zeitstempel}.zip";
        $zielPfad = BACKUPS_PATH . '/' . $dateiname;

        $zip = new ZipArchive();
        $zip->open($zielPfad, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile(DB_PATH, 'kunstverwaltung.sqlite');

        $bilderDateien = glob(BILDER_PATH . '/*') ?: [];
        foreach ($bilderDateien as $pfad) {
            if (is_file($pfad) && basename($pfad) !== '.gitkeep') {
                $zip->addFile($pfad, 'bilder/' . basename($pfad));
            }
        }
        $zip->close();

        $groesse = filesize($zielPfad) ?: 0;
        $ins = $pdo->prepare('INSERT INTO backups (dateiname, erstellt_von, dateigroesse) VALUES (:d, :b, :g)');
        $ins->execute(['d' => $dateiname, 'b' => $aktuellerBenutzer['id'], 'g' => $groesse]);

        Helpers::flashSet('erfolg', "Backup „{$dateiname}“ wurde erstellt.");
    } elseif ($aktion === 'loeschen') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM backups WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $backup = $stmt->fetch();
        if ($backup !== false) {
            @unlink(BACKUPS_PATH . '/' . $backup['dateiname']);
            $del = $pdo->prepare('DELETE FROM backups WHERE id = :id');
            $del->execute(['id' => $id]);
            Helpers::flashSet('erfolg', 'Backup wurde gelöscht.');
        }
    }

    Helpers::redirect('/backup.php');
}

$backups = $pdo->query('SELECT * FROM backups ORDER BY erstellt_am DESC')->fetchAll();

render('backup_liste', [
    'titel' => 'Backup',
    'aktuelleSeite' => 'backup',
    'backups' => $backups,
]);
