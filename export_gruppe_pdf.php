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
$istAdmin = Auth::isAdmin($benutzer);

// ── Felder und Profil-Optionen auflösen ──────────────────────────────────────
$alleFelder = Felder::alle();
$alleKeys   = array_column($alleFelder, 'key');

// Explizites Profil aus GET
$profilId = ($_GET['profil_id'] ?? '') !== '' ? (int) $_GET['profil_id'] : null;
$profil   = $profilId !== null ? ExportProfile::finde($pdo, $profilId) : null;

// Eingeschränkte Benutzer: freie Feldauswahl ignorieren; nur freigegebene Profile erlaubt
if (!$istAdmin) {
    // felder[] aus GET verwerfen
    $getFelder = null;
    // Profil muss für eingeschränkte Benutzer freigegeben sein
    if ($profil !== null && !$profil['fuer_eingeschraenkte']) {
        Protokoll::schreibe('export_verweigert', "PDF-Profil „{$profil['name']}“ für „{$gruppe['name']}“");
        Helpers::abbrechen(403, t('export.keine_rechte'));
    }
} else {
    // Felder aus GET-Array?
    $getFelder = isset($_GET['felder']) && is_array($_GET['felder'])
        ? array_values(array_filter((array) $_GET['felder'], static fn(mixed $k): bool => in_array($k, $alleKeys, true)))
        : null;
}

$spracheQuelle = null;

if ($getFelder !== null) {
    // GET-Felder in Registry-Reihenfolge bringen
    $pdfFelder       = array_values(array_filter($alleFelder, static fn(array $f): bool => in_array($f['key'], $getFelder, true)));
    $zeigeBild       = ($_GET['bild']             ?? '1') !== '0';
    $zeigeTitelblock = ($_GET['titelblock']        ?? '1') !== '0';
    $leerAusblenden  = ($_GET['leer_ausblenden']   ?? '1') !== '0';
    $zeigeSumme      = ($_GET['summe']             ?? '1') !== '0';
    $layout          = ExportProfile::layout($_GET['layout'] ?? '');
    $kachelRaster    = ExportProfile::kachelRaster($_GET['kachel_raster'] ?? '');
    $spracheQuelle   = $profil; // Sprachpräferenz aus explizit gewähltem Profil
} elseif ($profil !== null) {
    $pdfFelder       = array_values(array_filter($alleFelder, static fn(array $f): bool => in_array($f['key'], ExportProfile::felderAusProfil($profil), true)));
    $zeigeBild       = (bool) $profil['bild'];
    $zeigeTitelblock = (bool) $profil['titelblock'];
    $leerAusblenden  = (bool) $profil['leer_ausblenden'];
    $zeigeSumme      = (bool) $profil['summe'];
    $layout          = ExportProfile::layout($profil['layout']);
    $kachelRaster    = ExportProfile::kachelRaster($profil['kachel_raster'] ?? '');
    $spracheQuelle   = $profil;
} else {
    // Fallback: Standard-Profil. Eingeschränkte Benutzer bekommen es nur, wenn es
    // für sie freigegeben ist; sonst das erste freigegebene Profil.
    $standardProfil = ExportProfile::standard($pdo);
    if (!$istAdmin && ($standardProfil === null || !$standardProfil['fuer_eingeschraenkte'])) {
        $freigegeben = array_values(array_filter(
            ExportProfile::alle($pdo),
            static fn(array $p): bool => (bool) $p['fuer_eingeschraenkte']
        ));
        $standardProfil = $freigegeben[0] ?? null;
    }
    if ($standardProfil !== null) {
        $pdfFelder       = array_values(array_filter($alleFelder, static fn(array $f): bool => in_array($f['key'], ExportProfile::felderAusProfil($standardProfil), true)));
        $zeigeBild       = (bool) $standardProfil['bild'];
        $zeigeTitelblock = (bool) $standardProfil['titelblock'];
        $leerAusblenden  = (bool) $standardProfil['leer_ausblenden'];
        $zeigeSumme      = (bool) $standardProfil['summe'];
        $layout          = ExportProfile::layout($standardProfil['layout']);
        $kachelRaster    = ExportProfile::kachelRaster($standardProfil['kachel_raster'] ?? '');
        $spracheQuelle   = $standardProfil;
    } else {
        // Letzter Fallback: in_pdf-Felder, alle Schalter an. Den Ankaufswert
        // sehen eingeschränkte Benutzer auch hier nicht.
        $pdfFelder       = array_values(array_filter($alleFelder, static fn(array $f): bool =>
            $f['in_pdf'] && ($istAdmin || $f['key'] !== 'ankaufswert')));
        $zeigeBild       = true;
        $zeigeTitelblock = true;
        $leerAusblenden  = true;
        $zeigeSumme      = true;
        $layout          = ExportProfile::LAYOUTS[0];
        $kachelRaster    = ExportProfile::KACHEL_RASTER[0];
    }
}

// Sprache aus Profil übernehmen (vor t()-Aufrufen im Template)
if ($spracheQuelle !== null && !empty($spracheQuelle['sprache'])) {
    I18n::setze((string) $spracheQuelle['sprache']);
}

Protokoll::schreibe('export_gruppe', "PDF „{$gruppe['name']}“ ({$layout})");
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
// Liste mit vielen Spalten im Querformat, damit nichts abgeschnitten wird;
// Kacheln 3x2 immer quer, 2x2 hoch.
$querformat = $layout === 'kachel'
    ? $kachelRaster === '3x2'
    : ($layout === 'liste' && count($pdfFelder) > 6);
$dompdf->setPaper('A4', $querformat ? 'landscape' : 'portrait');
$dompdf->render();
$dompdf->stream(Export::dateiname($gruppe['name'], 'pdf'), ['Attachment' => true]);
