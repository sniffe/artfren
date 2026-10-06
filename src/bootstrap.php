<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

// Vollständigkeit von src/ und vendor/ prüft bereits upload_pruefung.php.
$autoloader = require APP_ROOT . '/vendor/autoload.php';

use App\Auth;
use App\Database;
use App\Einstellungen;
use App\Helpers;
use App\I18n;
use App\Migration;

// Fehler nie im Browser anzeigen (Pfade, SQL, Stacktraces), sondern protokollieren.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (!is_dir(LOG_PATH)) {
    @mkdir(LOG_PATH, 0755, true);
}
$fehlerLog = LOG_PATH . '/php-fehler.log';
if (is_file($fehlerLog) && filesize($fehlerLog) > 5 * 1024 * 1024) {
    @rename($fehlerLog, $fehlerLog . '.1');
}
ini_set('error_log', $fehlerLog);

set_exception_handler(static function (\Throwable $e) use ($autoloader): void {
    // Fehlt eine Programmdatei (unvollständiger Upload), das verständlich sagen.
    if (function_exists('kv_fehlende_datei') && ($pfad = kv_fehlende_datei($e, $autoloader->getPrefixesPsr4())) !== null) {
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
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Fehler</title>'
        . '<link rel="stylesheet" href="/assets/tokens.css"><link rel="stylesheet" href="/assets/style.css"></head>'
        . '<body><main class="hauptinhalt" style="max-width:560px"><div class="karte">'
        . '<h2>Es ist ein Fehler aufgetreten</h2>'
        . '<p>Die Aktion konnte nicht abgeschlossen werden. Bitte erneut versuchen.</p>'
        . '<p class="text-klein text-sekundaer">Fehler-ID für den Administrator: ' . $fehlerId . '</p>'
        . '<a class="btn" href="/">Zur Startseite</a></div></main></body></html>';
});

$https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['SERVER_PORT'] ?? '') === '443'
    // Hinter Proxys/CDNs (z. B. Cloudflare) kommt HTTPS nur als Header an.
    || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
    || strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on';

if (!Database::existiert()) {
    Helpers::redirect('/install.php');
}
// Datenbank-Updates laufen automatisch, sobald neue Programmdateien hochgeladen wurden.
Migration::aktualisiere(Database::get());

if (Einstellungen::httpsErzwingen() && !$https) {
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if (preg_match('/^[a-z0-9.-]+(:\d{1,5})?$/i', $host)) {
        header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('kv_sid');
ini_set('session.use_strict_mode', '1');
session_start();

// Sprache erkennen und setzen (Prio: Benutzer → ?lang= / Cookie → Accept-Language → Admin-Standard → de)
I18n::setze(I18n::erkennen($_SESSION['sprache'] ?? null, Einstellungen::standardSprache()));

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cross-Origin-Opener-Policy: same-origin');
// Kein Inline-JavaScript erlaubt: selbst eingeschleustes <script> würde nicht ausgeführt.
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'self'; base-uri 'self'; object-src 'none'");
if ($https) {
    header('Strict-Transport-Security: max-age=31536000');
}

/**
 * Rendert ein Template innerhalb des gemeinsamen Layouts. Die Schlüssel von
 * $daten stehen im Template als Variablen zur Verfügung, ebenso immer
 * $aktuellerBenutzer.
 */
function render(string $template, array $daten = []): void
{
    $aktuellerBenutzer = Auth::currentUser();
    $daten['aktuellerBenutzer'] = $aktuellerBenutzer;
    $inhaltDatei = APP_ROOT . '/templates/' . $template . '.php';

    $render = static function () use ($inhaltDatei, $daten): void {
        extract($daten, EXTR_SKIP);
        require $inhaltDatei;
    };

    ob_start();
    $render();
    $inhalt = ob_get_clean();

    $flashes = Helpers::flashGetAll();
    extract($daten, EXTR_SKIP);

    require APP_ROOT . '/templates/layout.php';
}
