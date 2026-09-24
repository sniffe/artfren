<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Bilder;
use App\Export;
use App\GruppeRepository;
use App\Helpers;
use App\Protokoll;
use App\WerkRepository;

$benutzer = Auth::requireLogin();
$id = (int) ($_GET['id'] ?? 0);
$gruppe = GruppeRepository::neu()->finde($id);
if ($gruppe === null || !Auth::darfGruppeSehen($benutzer, $id)) {
    Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.');
}

Protokoll::schreibe('export_gruppe', "ZIP „{$gruppe['name']}“");
session_write_close();
@set_time_limit(300);

$werke = WerkRepository::neu()->fuerGruppe($id);

$tmpZip = tempnam(sys_get_temp_dir(), 'kv_zip_');
$zip = new ZipArchive();
$zip->open($tmpZip, ZipArchive::OVERWRITE);

$csv = fopen('php://temp', 'w+');
Export::schreibeCsv($csv, $werke);
rewind($csv);
$zip->addFromString('kunstwerke.csv', (string) stream_get_contents($csv));
fclose($csv);

$hinzugefuegt = [];
foreach ($werke as $werk) {
    $name = $werk['bild_dateiname'];
    if (!$name || isset($hinzugefuegt[$name])) {
        continue;
    }
    $pfad = Bilder::originalPfad($name);
    if (is_file($pfad)) {
        // Originaldateinamen beibehalten: die CSV verweist in "Dateiname" genau darauf.
        $zip->addFile($pfad, 'bilder/' . basename($name));
        $hinzugefuegt[$name] = true;
    }
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . Export::dateiname($gruppe['name'], 'zip') . '"');
header('Content-Length: ' . filesize($tmpZip));
readfile($tmpZip);
unlink($tmpZip);
