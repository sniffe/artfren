<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Helpers;
use App\Migration;
use App\Protokoll;
use App\Sicherheitscheck;

Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    if (($_POST['aktion'] ?? '') === 'pruefen') {
        $_SESSION['sicherheitscheck'] = ['zeit' => time(), 'ergebnis' => Sicherheitscheck::pruefeOrdner()];
    }
    Helpers::redirect('/system.php');
}

$proSeite = 100;
$seite = max(1, (int) ($_GET['seite'] ?? 1));
$pdo = Database::get();

$https = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
    || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

render('system', [
    'titel' => 'System',
    'aktuelleSeite' => 'system',
    'breit' => true,
    'pruefung' => $_SESSION['sicherheitscheck'] ?? null,
    'info' => [
        'PHP-Version' => PHP_VERSION,
        'Speicherlimit (memory_limit)' => ini_get('memory_limit') === '-1' ? 'unbegrenzt' : (string) ini_get('memory_limit'),
        'Max. Laufzeit (max_execution_time)' => ini_get('max_execution_time') . ' s',
        'Max. Upload je Datei' => (string) ini_get('upload_max_filesize'),
        'Max. Upload gesamt (post_max_size)' => (string) ini_get('post_max_size'),
        'Datenbankgröße' => Helpers::formatGroesse((int) filesize(DB_PATH)),
        'Schema-Version' => Migration::aktuelleVersion($pdo) . ' / ' . Migration::zielVersion(),
        'Verbindung' => $https ? 'HTTPS (verschlüsselt)' : 'HTTP (unverschlüsselt)',
        'HTTPS erzwingen' => HTTPS_ERZWINGEN ? 'ja' : 'nein (src/config.php)',
    ],
    'httpsAktiv' => $https,
    'protokoll' => Protokoll::letzte($proSeite + 1, ($seite - 1) * $proSeite),
    'proSeite' => $proSeite,
    'seite' => $seite,
]);
