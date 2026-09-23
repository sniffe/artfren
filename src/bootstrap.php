<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require APP_ROOT . '/vendor/autoload.php';

use App\Helpers;

$sicher = (($_SERVER['HTTPS'] ?? '') !== '') || (($_SERVER['SERVER_PORT'] ?? '') === '443');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $sicher,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name('peterkunst_sid');
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header("Referrer-Policy: same-origin");

/**
 * Rendert ein Template mit den übergebenen Variablen innerhalb des
 * gemeinsamen Layouts. $daten-Schlüssel stehen im Template als Variablen
 * zur Verfügung.
 */
function render(string $template, array $daten = []): void
{
    extract($daten, EXTR_SKIP);
    $inhaltDatei = APP_ROOT . '/templates/' . $template . '.php';

    $render = static function () use ($inhaltDatei, $daten) {
        extract($daten, EXTR_SKIP);
        require $inhaltDatei;
    };

    ob_start();
    $render();
    $inhalt = ob_get_clean();

    $aktuellerBenutzer = \App\Auth::currentUser();
    $flashes = Helpers::flashGetAll();

    require APP_ROOT . '/templates/layout.php';
}
