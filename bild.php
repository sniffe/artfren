<?php
declare(strict_types=1);

// Liefert Bilder nur an angemeldete Benutzer aus, die das zugehörige Werk
// sehen dürfen. Der Ordner bilder/ selbst ist von außen gesperrt.

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Bilder;

$benutzer = Auth::requireLogin();

$bildId = (int) ($_GET['id'] ?? 0);
$groesse = (string) ($_GET['g'] ?? 'm');
if (!in_array($groesse, ['t', 'm', 'g', 'o'], true)) {
    $groesse = 'm';
}

// Original-Dateien nur für Admins und Benutzer mit Bilder-Export-Recht
if ($groesse === 'o' && !Auth::isAdmin($benutzer) && !($benutzer['darf_bilder_export'] ?? 0)) {
    $groesse = 'g';
}

$bild = Auth::erlaubtesBild($benutzer, $bildId);

// Sitzung sofort freigeben: sonst warten die vielen parallelen Bildanfragen
// einer Seite aufeinander (PHP sperrt die Sitzungsdatei pro Anfrage).
session_write_close();

if ($bild === null) {
    http_response_code(404);
    exit;
}

$pfad = Bilder::pfad($bild['dateiname'], $groesse);
if ($pfad === null) {
    http_response_code(404);
    exit;
}

$mtime = (int) filemtime($pfad);
$etag = '"' . sha1($pfad . $mtime) . '"';
header('Cache-Control: private, max-age=2592000');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}

header('Content-Type: ' . ($groesse === 'o' ? Bilder::mimeTyp($pfad) : 'image/jpeg'));
header('Content-Length: ' . filesize($pfad));
if ($groesse === 'o') {
    header('Content-Disposition: inline; filename="' . rawurlencode($bild['dateiname']) . '"');
}
readfile($pfad);
