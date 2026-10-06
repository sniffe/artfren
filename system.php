<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use App\Einstellungen;
use App\Helpers;
use App\I18n;
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
        $neueSprache = (string) ($_POST['standard_sprache'] ?? '');
        // $https stammt aus bootstrap.php. Einschalten nur über HTTPS, damit man
        // sich nicht aussperrt, falls der Server gar kein Zertifikat hat.
        $fehler = Einstellungen::appNameFehler($name)
            ?? ($httpsGewuenscht && !$https
                ? t('system.https_hinweis')
                : null);
        if ($fehler !== null) {
            Helpers::flashSet('fehler', $fehler);
        } else {
            $vorher = Einstellungen::appName();
            Einstellungen::setze('app_name', $name);
            Einstellungen::setze('https_erzwingen', $httpsGewuenscht ? '1' : '0');
            if (isset(I18n::SPRACHEN[$neueSprache])) {
                Einstellungen::setze('standard_sprache', $neueSprache);
            }
            Protokoll::schreibe('einstellungen', “Name: „{$vorher}” → „{$name}”, HTTPS: “ . ($httpsGewuenscht ? 'ja' : 'nein'));
            Helpers::flashSet('erfolg', t('system.gespeichert'));
        }
    } elseif ($aktion === 'aufraeumen') {
        $ergebnis = Wartung::loescheVeraltete();
        if ($ergebnis['geloescht'] !== []) {
            Protokoll::schreibe('aufraeumen', 'Veraltete Dateien gelöscht: ' . implode(', ', $ergebnis['geloescht']));
            Helpers::flashSet('erfolg', t('system.veraltet_geloescht'));
        }
        if ($ergebnis['fehler'] !== []) {
            Helpers::flashSet('fehler', t('system.loeschen_fehler', ['dateien' => implode(', ', $ergebnis['fehler'])]));
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
    $warnungen[] = t('system.warnung_post_max');
}
if (!$webpUnterstuetzt) {
    $warnungen[] = t('system.warnung_webp');
}

render('system', [
    'titel' => t('system.titel'),
    'standardSprache' => Einstellungen::standardSprache(),
    'aktuelleSeite' => 'system',
    'breit' => true,
    'pruefung' => $_SESSION['sicherheitscheck'] ?? null,
    'info' => [
        t('system.info_version')        => APP_VERSION,
        t('system.info_php')            => PHP_VERSION,
        t('system.info_speicher')       => ini_get('memory_limit') === '-1' ? t('system.info_unbegrenzt') : (string) ini_get('memory_limit'),
        t('system.info_laufzeit')       => ini_get('max_execution_time') . ' s',
        t('system.info_upload_datei')   => (string) ini_get('upload_max_filesize'),
        t('system.info_upload_gesamt')  => (string) ini_get('post_max_size'),
        t('system.info_upload_anzahl')  => (string) ini_get('max_file_uploads'),
        t('system.info_gd')             => function_exists('gd_info')
            ? ($webpUnterstuetzt ? t('system.info_gd_webp_ja') : t('system.info_gd_webp_nein'))
            : t('system.info_nicht_vorhanden'),
        t('system.info_zip')            => $zipUnterstuetzt ? t('system.info_vorhanden') : t('system.info_nicht_vorhanden'),
        t('system.info_db_groesse')     => Helpers::formatGroesse((int) filesize(DB_PATH)),
        t('system.info_schema')         => Migration::aktuelleVersion($pdo) . ' / ' . Migration::zielVersion(),
        t('system.info_ordner_bilder')  => Helpers::formatGroesse(Helpers::ordnerGroesse(BILDER_PATH)),
        t('system.info_ordner_backups') => Helpers::formatGroesse(Helpers::ordnerGroesse(BACKUPS_PATH)),
        t('system.info_speicher_frei')  => $freierSpeicher !== false
            ? Helpers::formatGroesse((int) $freierSpeicher)
            : t('system.info_unbekannt'),
        t('system.info_verbindung')     => $https ? t('system.info_https') : t('system.info_http'),
        t('system.info_https_erzwingen') => Einstellungen::httpsErzwingen() ? t('system.info_ja') : t('system.info_nein'),
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
