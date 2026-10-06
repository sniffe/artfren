<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Backup;
use App\Database;
use App\Helpers;
use App\Protokoll;

$benutzer = Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'erstellen') {
        $mitBildern = ($_POST['umfang'] ?? '') !== 'nur_datenbank';
        $ergebnis = Backup::erstellen($mitBildern, $benutzer);
        Protokoll::schreibe('backup_erstellt', $ergebnis['dateiname'] . ' (' . Helpers::formatGroesse($ergebnis['groesse']) . ')');
        Helpers::flashSet('erfolg', t('backup.erstellt_datei', ['name' => $ergebnis['dateiname']]));
    } elseif ($aktion === 'loeschen') {
        $backup = Backup::finde((int) ($_POST['id'] ?? 0));
        if ($backup !== null) {
            Backup::loeschen($backup);
            Protokoll::schreibe('backup_geloescht', $backup['dateiname']);
            Helpers::flashSet('erfolg', t('backup.geloescht'));
        }
    }

    Helpers::redirect('/backup.php');
}

$bilderGroesse = 0;
$bilderAnzahl = 0;
foreach (new DirectoryIterator(BILDER_PATH) as $datei) {
    if ($datei->isFile() && !str_starts_with($datei->getFilename(), '.')) {
        $bilderGroesse += $datei->getSize();
        $bilderAnzahl++;
    }
}

render('backup_liste', [
    'titel' => t('backup.titel'),
    'aktuelleSeite' => 'backup',
    'backups' => Database::get()->query('SELECT * FROM backups ORDER BY id DESC')->fetchAll(),
    'bilderGroesse' => $bilderGroesse,
    'bilderAnzahl' => $bilderAnzahl,
]);
