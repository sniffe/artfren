<?php
declare(strict_types=1);

// Schlanker Bootstrap für öffentliche Galerie-Seiten.
// Kein Session-Start, kein Cookie-Overhead für offene Galerien.

require __DIR__ . '/config.php';

if (!is_file(APP_ROOT . '/vendor/autoload.php')) {
    http_response_code(500);
    exit;
}
require APP_ROOT . '/vendor/autoload.php';

use App\Database;
use App\Einstellungen;
use App\I18n;
use App\Migration;

ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (!is_dir(LOG_PATH)) {
    @mkdir(LOG_PATH, 0755, true);
}
ini_set('error_log', LOG_PATH . '/php-fehler.log');

set_exception_handler(static function (\Throwable $e): void {
    // Fehlt eine Programmdatei (unvollständiger Upload), das verständlich sagen.
    if (function_exists('kv_fehlende_datei') && ($pfad = kv_fehlende_datei($e)) !== null) {
        error_log('Programmdatei fehlt: ' . $pfad);
        kv_upload_unvollstaendig([$pfad]);
    }
    $fehlerId = bin2hex(random_bytes(4));
    error_log("[Fehler-ID {$fehlerId}] " . $e);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Fehler</title>'
        . '<link rel="stylesheet" href="/assets/tokens.css"><link rel="stylesheet" href="/assets/web.css"></head>'
        . '<body><div class="web-fehler"><p>Ein Fehler ist aufgetreten.</p></div></body></html>';
    exit;
});

$https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['SERVER_PORT'] ?? '') === '443'
    || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
    || strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on';

if (!Database::existiert()) {
    http_response_code(404);
    exit;
}

Migration::aktualisiere(Database::get());

// Sprache erkennen (öffentliche Seiten: kein eingeloggter Benutzer)
I18n::setze(I18n::erkennen(null, Einstellungen::standardSprache()));

if (Einstellungen::httpsErzwingen() && !$https) {
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if (preg_match('/^[a-z0-9.-]+(:\d{1,5})?$/i', $host)) {
        header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow, noarchive');

/**
 * Rendert ein öffentliches Template im Web-Layout.
 *
 * @param string $frameAncestors Frame-ancestors Wert für die CSP dieser Seite.
 */
function renderPublic(string $template, array $daten = [], string $frameAncestors = "'none'"): void
{
    global $https;
    $daten['appName'] = \App\Einstellungen::appName();

    $inhaltDatei = APP_ROOT . '/templates/' . $template . '.php';
    $render = static function () use ($inhaltDatei, $daten): void {
        extract($daten, EXTR_SKIP);
        require $inhaltDatei;
    };
    ob_start();
    $render();
    $inhalt = ob_get_clean();

    extract($daten, EXTR_SKIP);
    $csp = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
         . "img-src 'self' data:; font-src 'self'; connect-src 'self'; form-action 'self'; "
         . "frame-ancestors {$frameAncestors}; base-uri 'self'; object-src 'none'";
    header("Content-Security-Policy: {$csp}");
    if ($https) {
        header('Strict-Transport-Security: max-age=31536000');
    }
    require APP_ROOT . '/templates/web_layout.php';
}
