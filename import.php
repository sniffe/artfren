<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\CsvImport;
use App\Database;
use App\Helpers;

$aktuellerBenutzer = Auth::requireAdmin();
$pdo = Database::get();

$importTmpDir = APP_ROOT . '/data/import_tmp';
if (!is_dir($importTmpDir)) {
    mkdir($importTmpDir, 0755, true);
}

function import_tmp_pfad(string $dateiname): string
{
    global $importTmpDir;
    // Nur Basename zulassen, um Path-Traversal über den hidden field-Wert auszuschließen.
    return $importTmpDir . '/' . basename($dateiname);
}

$schritt = 'upload';
$daten = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = (string) ($_POST['aktion'] ?? '');

    if ($aktion === 'vorschau') {
        if (empty($_FILES['csv_datei']) || $_FILES['csv_datei']['error'] !== UPLOAD_ERR_OK) {
            Helpers::flashSet('fehler', 'Es wurde keine gültige CSV-Datei hochgeladen.');
            Helpers::redirect('/import.php');
        }

        $tmpName = uniqid('import_', true) . '.csv';
        $zielPfad = import_tmp_pfad($tmpName);
        if (!move_uploaded_file($_FILES['csv_datei']['tmp_name'], $zielPfad)) {
            Helpers::flashSet('fehler', 'Die Datei konnte nicht gespeichert werden.');
            Helpers::redirect('/import.php');
        }

        try {
            $vorschau = CsvImport::vorschau($zielPfad, 20);
        } catch (\RuntimeException $e) {
            unlink($zielPfad);
            Helpers::flashSet('fehler', $e->getMessage());
            Helpers::redirect('/import.php');
        }

        $mapping = CsvImport::automatischesMapping($vorschau['header']);

        $schritt = 'mapping';
        $daten = [
            'header' => $vorschau['header'],
            'zeilen' => $vorschau['zeilen'],
            'gesamtzeilen' => $vorschau['gesamtzeilen'],
            'trennzeichen' => $vorschau['trennzeichen'],
            'mapping' => $mapping,
            'tmp_datei' => $tmpName,
        ];
    } elseif ($aktion === 'abgleich' || $aktion === 'uebernehmen') {
        $tmpName = (string) ($_POST['tmp_datei'] ?? '');
        $trennzeichen = (string) ($_POST['trennzeichen'] ?? ',');
        $mapping = array_map('strval', $_POST['mapping'] ?? []);
        $pfad = import_tmp_pfad($tmpName);

        if (!is_file($pfad)) {
            Helpers::flashSet('fehler', 'Die Import-Datei ist nicht mehr verfügbar. Bitte erneut hochladen.');
            Helpers::redirect('/import.php');
        }

        $zeilen = CsvImport::leseAlleZeilen($pfad, $mapping, $trennzeichen);
        $abgleich = CsvImport::berechneAbgleich($pdo, $zeilen);

        if ($aktion === 'abgleich') {
            $schritt = 'abgleich';
            $daten = [
                'abgleich' => $abgleich,
                'tmp_datei' => $tmpName,
                'trennzeichen' => $trennzeichen,
                'mapping' => $mapping,
                'gesamtzeilen' => count($zeilen),
            ];
        } else {
            CsvImport::uebernehmen($pdo, $abgleich);
            @unlink($pfad);

            $schritt = 'ergebnis';
            $daten = [
                'anzahlNeu' => count($abgleich['neu']),
                'anzahlGeaendert' => count($abgleich['geaendert']),
                'anzahlUnveraendert' => $abgleich['unveraendert'],
                'anzahlFehler' => count($abgleich['fehlerBild']),
                'fehlerBild' => $abgleich['fehlerBild'],
            ];
        }
    }
}

render('import_' . $schritt, array_merge($daten, [
    'titel' => 'CSV-Import',
    'aktuelleSeite' => 'import',
]));
