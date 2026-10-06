<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Bilder;
use App\Database;
use App\Export;
use App\ExportProfile;
use App\Felder;
use App\GruppeRepository;
use App\Helpers;
use App\I18n;
use App\Protokoll;
use App\WerkRepository;
use Dompdf\Dompdf;
use Dompdf\Options;

$benutzer = Auth::requireLogin();
$id = (int) ($_GET['id'] ?? 0);
$gruppe = GruppeRepository::neu()->finde($id);
if ($gruppe === null || !Auth::darfGruppeSehen($benutzer, $id)) {
    Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.');
}

$pdo = Database::get();

// ── Felder und Profil-Optionen auflösen ──────────────────────────────────────
$alleFelder = Felder::alle();
$alleKeys   = array_column($alleFelder, 'key');

// Explizites Profil aus GET
$profilId = ($_GET['profil_id'] ?? '') !== '' ? (int) $_GET['profil_id'] : null;
$profil   = $profilId !== null ? ExportProfile::finde($pdo, $profilId) : null;

// Felder aus GET-Array?
$getFelder = isset($_GET['felder']) && is_array($_GET['felder'])
    ? array_values(array_filter((array) $_GET['felder'], static fn(mixed $k): bool => in_array($k, $alleKeys, true)))
    : null;

$spracheQuelle = null;

if ($getFelder !== null) {
    // GET-Felder in Registry-Reihenfolge bringen
    $pdfFelder       = array_values(array_filter($alleFelder, static fn(array $f): bool => in_array($f['key'], $getFelder, true)));
    $zeigeBild       = ($_GET['bild']             ?? '1') !== '0';
    $zeigeTitelblock = ($_GET['titelblock']        ?? '1') !== '0';
    $leerAusblenden  = ($_GET['leer_ausblenden']   ?? '1') !== '0';
    $zeigeSumme      = ($_GET['summe']             ?? '1') !== '0';
    $layout          = in_array($_GET['layout'] ?? '', ['liste', 'einzelblatt'], true) ? (string) $_GET['layout'] : 'einzelblatt';
    $spracheQuelle   = $profil; // Sprachpräferenz aus explizit gewähltem Profil
} elseif ($profil !== null) {
    $pdfFelder       = array_values(array_filter($alleFelder, static fn(array $f): bool => in_array($f['key'], ExportProfile::felderAusProfil($profil), true)));
    $zeigeBild       = (bool) $profil['bild'];
    $zeigeTitelblock = (bool) $profil['titelblock'];
    $leerAusblenden  = (bool) $profil['leer_ausblenden'];
    $zeigeSumme      = (bool) $profil['summe'];
    $layout          = (string) $profil['layout'];
    $spracheQuelle   = $profil;
} else {
    // Fallback: Standard-Profil
    $standardProfil = ExportProfile::standard($pdo);
    if ($standardProfil !== null) {
        $pdfFelder       = array_values(array_filter($alleFelder, static fn(array $f): bool => in_array($f['key'], ExportProfile::felderAusProfil($standardProfil), true)));
        $zeigeBild       = (bool) $standardProfil['bild'];
        $zeigeTitelblock = (bool) $standardProfil['titelblock'];
        $leerAusblenden  = (bool) $standardProfil['leer_ausblenden'];
        $zeigeSumme      = (bool) $standardProfil['summe'];
        $layout          = (string) $standardProfil['layout'];
        $spracheQuelle   = $standardProfil;
    } else {
        // Letzter Fallback: in_pdf-Felder, alle Schalter an
        $pdfFelder       = array_values(array_filter($alleFelder, static fn(array $f): bool => $f['in_pdf']));
        $zeigeBild       = true;
        $zeigeTitelblock = true;
        $leerAusblenden  = true;
        $zeigeSumme      = true;
        $layout          = 'einzelblatt';
    }
}

// Sprache aus Profil übernehmen (vor t()-Aufrufen im Template)
if ($spracheQuelle !== null && !empty($spracheQuelle['sprache'])) {
    I18n::setze((string) $spracheQuelle['sprache']);
}

Protokoll::schreibe('export_gruppe', "PDF „{$gruppe['name']}" ({$layout})");
session_write_close();
@set_time_limit(300);

$werke = WerkRepository::neu()->fuerGruppe($id);
foreach ($werke as &$werk) {
    // Mittlere Größe (800 px) statt Original: hält Speicherbedarf und PDF-Größe klein.
    $pfad = $werk['bild_dateiname'] ? Bilder::pfad($werk['bild_dateiname'], 'm') : null;
    $werk['bild_data_uri'] = $pfad ? 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents($pfad)) : null;
}
unset($werk);

ob_start();
require APP_ROOT . '/templates/pdf_gruppe.php';
$html = (string) ob_get_clean();

$optionen = new Options();
$optionen->set('isRemoteEnabled', false);
$optionen->set('isPhpEnabled', false);
$optionen->set('defaultFont', 'DejaVu Sans');
$optionen->set('chroot', APP_ROOT . '/vendor/dompdf/dompdf');

$dompdf = new Dompdf($optionen);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream(Export::dateiname($gruppe['name'], 'pdf'), ['Attachment' => true]);
