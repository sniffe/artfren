<?php
declare(strict_types=1);

// Liefert Bilder öffentlicher Galerien aus.
// Nur Größen 'm' und 'g' – nie 'o' (Original).
// Sicherheit: Werk muss zur aktiven Gruppe gehören und web_freigabe=1 haben.

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap_public.php';

use App\Bilder;
use App\GruppeRepository;
use App\WebGruppe;

$token   = (string) ($_GET['t'] ?? '');
$bildId  = (int) ($_GET['id'] ?? 0);
$groesse = (string) ($_GET['g'] ?? 'm');

// Nur m und g erlaubt; Original und Thumbnail nie öffentlich
if (!in_array($groesse, ['m', 'g'], true)) {
    http_response_code(404);
    exit;
}

if (!preg_match('/^[0-9a-f]{32}$/', $token) || $bildId <= 0) {
    http_response_code(404);
    exit;
}

$repo   = GruppeRepository::neu();
$gruppe = $repo->findePerToken($token);

// Admin-Vorschau auch für Bilder erlauben
$adminVorschau = false;
if (isset($_COOKIE['kv_sid'])) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_name('kv_sid');
    @session_start();
    $adminVorschau = \App\Auth::isAdmin(\App\Auth::currentUser());
    session_write_close();
}

if ($gruppe === null || (!WebGruppe::istZugaenglich($gruppe) && !$adminVorschau)) {
    http_response_code(404);
    exit;
}

if (WebGruppe::brauchPasswort($gruppe) && !WebGruppe::hatCookieZugang($gruppe) && !$adminVorschau) {
    http_response_code(403);
    exit;
}

// Bild muss zu einem öffentlichen, nicht herausgefilterten Werk dieser Gruppe gehören
$dateiname = GruppeRepository::neu()->bildOeffentlich((int) $gruppe['id'], $bildId);

if ($dateiname === null) {
    http_response_code(404);
    exit;
}

$pfad = Bilder::pfad($dateiname, $groesse);
if ($pfad === null) {
    http_response_code(404);
    exit;
}

$mtime = (int) filemtime($pfad);
$etag  = '"' . sha1($pfad . $mtime) . '"';
header('Cache-Control: private, max-age=3600');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('X-Robots-Tag: noindex');

if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($pfad));
readfile($pfad);
