<?php
declare(strict_types=1);

// Erzeugt bei Bedarf ein Thumbnail für ein Bild aus /bilder und cacht es
// dauerhaft unter /thumbs, damit es beim nächsten Aufruf direkt als
// statische Datei ausgeliefert werden kann (kein Cronjob nötig).

require __DIR__ . '/src/bootstrap.php';

use App\Auth;

Auth::requireLogin();

$dateiname = basename((string) ($_GET['f'] ?? ''));
if ($dateiname === '') {
    http_response_code(400);
    exit;
}

$quelle = BILDER_PATH . '/' . $dateiname;
$ziel = THUMBS_PATH . '/' . $dateiname;

if (!is_file($quelle)) {
    http_response_code(404);
    exit;
}

if (!is_file($ziel) || filemtime($ziel) < filemtime($quelle)) {
    $inhalt = file_get_contents($quelle);
    $bild = $inhalt !== false ? @imagecreatefromstring($inhalt) : false;

    if ($bild === false) {
        http_response_code(415);
        exit('Bildformat wird nicht unterstützt.');
    }

    $breiteQuelle = imagesx($bild);
    $hoeheQuelle = imagesy($bild);
    $breiteZiel = THUMB_BREITE;
    $hoeheZiel = (int) round($hoeheQuelle * ($breiteZiel / $breiteQuelle));

    $thumb = imagecreatetruecolor($breiteZiel, $hoeheZiel);
    $weiss = imagecolorallocate($thumb, 255, 255, 255);
    imagefill($thumb, 0, 0, $weiss);
    imagecopyresampled($thumb, $bild, 0, 0, 0, 0, $breiteZiel, $hoeheZiel, $breiteQuelle, $hoeheQuelle);

    if (!is_dir(THUMBS_PATH)) {
        mkdir(THUMBS_PATH, 0755, true);
    }
    imagejpeg($thumb, $ziel, 85);
    imagedestroy($thumb);
    imagedestroy($bild);
}

header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=2592000');
readfile($ziel);
