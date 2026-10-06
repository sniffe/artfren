<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Einstellungen;
use App\Helpers;
use App\Migration;
use App\Protokoll;
use App\Sicherheitscheck;
use App\Wartung;

Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Helpers::checkCsrf();
    $aktion = $_POST['aktion'] ?? '';
    if ($aktion === 'pruefen') {
        $_SESSION['sicherheitscheck'] = ['zeit' => time(), 'ergebnis' => Sicherheitscheck::pruefeOrdner()];
    } elseif ($aktion === 'einstellungen') {
        $name = trim((string) ($_POST['app_name'] ?? ''));
        $httpsGewuenscht = isset($_POST['https_erzwingen']);
        // $https stammt aus bootstrap.php. Einschalten nur über HTTPS, damit man
        // sich nicht aussperrt, falls der Server gar kein Zertifikat hat.
        $fehler = Einstellungen::appNameFehler($name)
            ?? ($httpsGewuenscht && !$https
                ? 'HTTPS erzwingen lässt sich nur einschalten, wenn die Seite bereits über https:// aufgerufen wird.'
                : null);
        if ($fehler !== null) {
            Helpers::flashSet('fehler', $fehler);
        } else {
            $vorher = Einstellungen::appName();
            Einstellungen::setze('app_name', $name);
            Einstellungen::setze('https_erzwingen', $httpsGewuenscht ? '1' : '0');
            Protokoll::schreibe('einstellungen', "Name: „{$vorher}“ → „{$name}“, HTTPS erzwingen: " . ($httpsGewuenscht ? 'ja' : 'nein'));
            Helpers::flashSet('erfolg', 'Einstellungen wurden gespeichert.');
        }
    } elseif ($aktion === 'aufraeumen') {
        $ergebnis = Wartung::loescheVeraltete();
        if ($ergebnis['geloescht'] !== []) {
            Protokoll::schreibe('aufraeumen', 'Veraltete Dateien gelöscht: ' . implode(', ', $ergebnis['geloescht']));
            Helpers::flashSet('erfolg', 'Gelöscht: ' . implode(', ', $ergebnis['geloescht']));
        }
        if ($ergebnis['fehler'] !== []) {
            Helpers::flashSet('fehler', 'Konnte nicht gelöscht werden (bitte per FTP entfernen): ' . implode(', ', $ergebnis['fehler']));
        }
    }
    Helpers::redirect('/system.php');
}

$proSeite = 100;
$seite = max(1, (int) ($_GET['seite'] ?? 1));
$pdo = Database::get();

$gdInfo = function_exists('gd_info') ? gd_info() : [];
$webpUnterstuetzt = ($gdInfo['WebP Support'] ?? false) === true;
$zipUnterstuetzt = class_exists('ZipArchive');
$postMaxBytes = Helpers::iniGroesseZuBytes((string) ini_get('post_max_size'));
$freierSpeicher = disk_free_space(DATA_PATH);

$warnungen = [];
if ($postMaxBytes > 0 && $postMaxBytes < 32 * 1024 * 1024) {
    $warnungen[] = 'post_max_size ist kleiner als 32 MB – der Upload größerer Bilder oder ZIP-Dateien kann fehlschlagen.';
}
if (!$webpUnterstuetzt) {
    $warnungen[] = 'GD unterstützt kein WebP – WebP-Bilder können nicht verarbeitet werden.';
}

render('system', [
    'titel' => 'System',
    'aktuelleSeite' => 'system',
    'breit' => true,
    'pruefung' => $_SESSION['sicherheitscheck'] ?? null,
    'info' => [
        'Anwendungsversion' => APP_VERSION,
        'PHP-Version' => PHP_VERSION,
        'Speicherlimit (memory_limit)' => ini_get('memory_limit') === '-1' ? 'unbegrenzt' : (string) ini_get('memory_limit'),
        'Max. Laufzeit (max_execution_time)' => ini_get('max_execution_time') . ' s',
        'Max. Upload je Datei (upload_max_filesize)' => (string) ini_get('upload_max_filesize'),
        'Max. Upload gesamt (post_max_size)' => (string) ini_get('post_max_size'),
        'Max. Dateien je Upload (max_file_uploads)' => (string) ini_get('max_file_uploads'),
        'GD-Bildverarbeitung' => function_exists('gd_info')
            ? 'vorhanden' . ($webpUnterstuetzt ? ', WebP: ja' : ', WebP: nein')
            : 'nicht vorhanden',
        'ZipArchive' => $zipUnterstuetzt ? 'vorhanden' : 'nicht vorhanden',
        'Datenbankgröße' => Helpers::formatGroesse((int) filesize(DB_PATH)),
        'Schema-Version (DB user_version)' => Migration::aktuelleVersion($pdo) . ' / ' . Migration::zielVersion(),
        'Ordner bilder/' => Helpers::formatGroesse(Helpers::ordnerGroesse(BILDER_PATH)),
        'Ordner backups/' => Helpers::formatGroesse(Helpers::ordnerGroesse(BACKUPS_PATH)),
        'Freier Speicher (data/)' => $freierSpeicher !== false
            ? Helpers::formatGroesse((int) $freierSpeicher)
            : 'unbekannt',
        'Verbindung' => $https ? 'HTTPS (verschlüsselt)' : 'HTTP (unverschlüsselt)',
        'HTTPS erzwingen' => Einstellungen::httpsErzwingen() ? 'ja' : 'nein',
    ],
    'warnungen' => $warnungen,
    'httpsAktiv' => $https,
    'appName' => Einstellungen::appName(),
    'httpsErzwingen' => Einstellungen::httpsErzwingen(),
    'veraltet' => Wartung::veralteteDateien(),
    'vollstaendigkeit' => Wartung::fehlendeProgrammdateien(),
    'fehlendeSchutzdateien' => Wartung::fehlendeSchutzdateien(),
    'protokoll' => Protokoll::letzte($proSeite + 1, ($seite - 1) * $proSeite),
    'proSeite' => $proSeite,
    'seite' => $seite,
]);
