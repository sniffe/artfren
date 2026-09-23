<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;

$aktuellerBenutzer = Auth::requireLogin();
$istAdmin = Auth::isAdmin($aktuellerBenutzer);
$pdo = Database::get();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM gruppen WHERE id = :id');
$stmt->execute(['id' => $id]);
$gruppe = $stmt->fetch();

if ($gruppe === false) {
    http_response_code(404);
    exit('Gruppe wurde nicht gefunden.');
}

if (!$istAdmin && !in_array($id, Auth::sichtbareGruppenIds($aktuellerBenutzer), true)) {
    http_response_code(403);
    exit('Zugriff verweigert.');
}

$werke = $pdo->prepare(
    "SELECT k.*, (
        SELECT dateiname FROM bilder b WHERE b.kunstwerk_id = k.id
        ORDER BY ist_hauptbild DESC, sortierung ASC LIMIT 1
    ) AS bild_dateiname
    FROM kunstwerke k
    JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id
    WHERE gk.gruppe_id = :id
    ORDER BY k.ort, k.maler, k.titel"
);
$werke->execute(['id' => $id]);
$werke = $werke->fetchAll();

$tmpZip = tempnam(sys_get_temp_dir(), 'gruppe_export_');
$zip = new ZipArchive();
$zip->open($tmpZip, ZipArchive::OVERWRITE);

$csv = fopen('php://temp', 'w+');
fwrite($csv, "\xEF\xBB\xBF");
fputcsv($csv, ['Ort', 'Maler', 'Titel', 'Format', 'Technik', 'Entstehungsjahr', 'Ankaufjahr', 'Ankauf', 'Ankaufswert', 'Wert', 'Typ', 'Status-Farbe', 'Bild-Dateiname'], ';');

$verwendeteNamen = [];
foreach ($werke as $w) {
    fputcsv($csv, [
        $w['ort'], $w['maler'], $w['titel'], $w['format'], $w['technik'],
        $w['entstehungsjahr'], $w['ankaufjahr'], $w['ankauf'], $w['ankaufswert'], $w['wert'],
        $w['werktyp'], $w['status_farbe'], $w['bild_dateiname'],
    ], ';');

    if ($w['bild_dateiname'] && is_file(BILDER_PATH . '/' . $w['bild_dateiname'])) {
        $name = $w['bild_dateiname'];
        $eindeutig = $name;
        $n = 2;
        while (isset($verwendeteNamen[$eindeutig])) {
            $eindeutig = pathinfo($name, PATHINFO_FILENAME) . '_' . $n . '.' . pathinfo($name, PATHINFO_EXTENSION);
            $n++;
        }
        $verwendeteNamen[$eindeutig] = true;
        $zip->addFile(BILDER_PATH . '/' . $name, 'bilder/' . $eindeutig);
    }
}

rewind($csv);
$csvInhalt = stream_get_contents($csv);
fclose($csv);
$zip->addFromString('kunstwerke.csv', $csvInhalt);
$zip->close();

$dateiname = preg_replace('/[^A-Za-z0-9_-]+/', '_', $gruppe['name']) . '_' . date('Y-m-d_Hi') . '.zip';

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $dateiname . '"');
header('Content-Length: ' . filesize($tmpZip));
readfile($tmpZip);
unlink($tmpZip);
