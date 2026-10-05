<?php
declare(strict_types=1);

// Liefert das App-Logo aus data/logo/ aus.
// Kein Login erforderlich (Logo erscheint auch auf öffentlichen Seiten).
// SVG wird mit strenger CSP und sandbox ausgeliefert, damit eingebettete
// Skripte niemals ausgeführt werden.

require __DIR__ . '/src/bootstrap_public.php';

use App\Einstellungen;

$logo = Einstellungen::logoPfad();
if ($logo === null) {
    http_response_code(404);
    exit;
}

$pfad = DATA_PATH . '/logo/' . $logo;
if (!is_file($pfad)) {
    http_response_code(404);
    exit;
}

$endung = strtolower(pathinfo($logo, PATHINFO_EXTENSION));
$mtime  = (int) filemtime($pfad);
$etag   = '"' . sha1($pfad . $mtime) . '"';

header('Cache-Control: public, max-age=86400, immutable');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}

if ($endung === 'svg') {
    // SVG isoliert ausliefern: eingebettete Skripte dürfen nie ausgeführt werden.
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
    header('Content-Type: image/svg+xml');
    header('Content-Disposition: inline');
} else {
    $mimeTypen = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'];
    header('Content-Type: ' . ($mimeTypen[$endung] ?? 'application/octet-stream'));
}

header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . (string) filesize($pfad));
readfile($pfad);
