<?php
declare(strict_types=1);

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Bilder;
use App\Database;
use App\Einstellungen;
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
    $deckblatt       = ($_GET['deckblatt'] ?? '0') === '1';
    $fusszeile       = ($_GET['fusszeile'] ?? '0') === '1';
    $spracheQuelle   = $profil; // Sprachpräferenz aus explizit gewähltem Profil
} elseif ($profil !== null) {
    $pdfFelder       = array_values(array_filter($alleFelder, static fn(array $f): bool => in_array($f['key'], ExportProfile::felderAusProfil($profil), true)));
    $zeigeBild       = (bool) $profil['bild'];
    $zeigeTitelblock = (bool) $profil['titelblock'];
    $leerAusblenden  = (bool) $profil['leer_ausblenden'];
    $zeigeSumme      = (bool) $profil['summe'];
    $layout          = ExportProfile::layout($profil['layout']);
    $kachelRaster    = ExportProfile::kachelRaster($profil['kachel_raster'] ?? '');
    $deckblatt       = (bool) ($profil['deckblatt'] ?? 0);
    $fusszeile       = (bool) ($profil['fusszeile'] ?? 0);
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
        $deckblatt       = (bool) ($standardProfil['deckblatt'] ?? 0);
        $fusszeile       = (bool) ($standardProfil['fusszeile'] ?? 0);
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
        $deckblatt       = false;
        $fusszeile       = false;
    }
}

// Den Ankaufswert sehen eingeschränkte Benutzer nie, auch nicht über ein für sie
// freigegebenes Profil, das ihn enthält (bis 1.2.6 möglich).
if (!$istAdmin) {
    $pdfFelder = array_values(array_filter($pdfFelder, static fn(array $f): bool => $f['key'] !== 'ankaufswert'));
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

// Liste mit vielen Spalten im Querformat, damit nichts abgeschnitten wird;
// Kacheln 3x2 immer quer, 2x2 hoch.
$querformat = $layout === 'kachel'
    ? $kachelRaster === '3x2'
    : ($layout === 'liste' && count($pdfFelder) > 6);

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
$dompdf->setPaper('A4', $querformat ? 'landscape' : 'portrait');
$dompdf->render();

// Fußzeile nach dem Rendern über die Canvas-API (ohne isPhpEnabled): links Copyright,
// Mitte Datum, rechts "Seite n von m". Das Deckblatt bleibt ohne Fußzeile und zählt nicht mit.
if ($fusszeile) {
    $copyright  = Einstellungen::hol('pdf_copyright');
    $datum      = date('d.m.Y');
    $seiteVorl  = t('pdf.seite_n_von_m', ['n' => '{n}', 'm' => '{m}']);
    $mitDeckbl  = $deckblatt;
    $dompdf->getCanvas()->page_script(static function (int $seite, int $seiten, $canvas, $fontMetrics) use ($copyright, $datum, $seiteVorl, $mitDeckbl): void {
        if ($mitDeckbl && $seite === 1) {
            return;
        }
        $n = $mitDeckbl ? $seite - 1 : $seite;
        $m = $mitDeckbl ? $seiten - 1 : $seiten;
        $schrift = $fontMetrics->getFont('DejaVu Sans');
        $groesse = 7.5;
        $farbe   = [0.42, 0.41, 0.38];
        $rand    = 34.0;                          // 12 mm, wie der Seitenrand
        $y       = $canvas->get_height() - 30.0;  // gut 1 cm über der Unterkante
        $breite  = $canvas->get_width();

        $rechts = str_replace(['{n}', '{m}'], [(string) $n, (string) $m], $seiteVorl);
        $wRechts = $fontMetrics->getTextWidth($rechts, $schrift, $groesse);
        $wMitte  = $fontMetrics->getTextWidth($datum, $schrift, $groesse);
        $canvas->text(($breite - $wMitte) / 2, $y, $datum, $schrift, $groesse, $farbe);
        $canvas->text($breite - $rand - $wRechts, $y, $rechts, $schrift, $groesse, $farbe);

        // Copyright kürzen, bis es links vor dem Datum Platz hat.
        $platz = ($breite - $wMitte) / 2 - $rand - 12.0;
        $links = $copyright;
        while ($links !== '' && $links !== '…' && $fontMetrics->getTextWidth($links, $schrift, $groesse) > $platz) {
            $links = rtrim(mb_substr(rtrim($links, '…'), 0, -1)) . '…';
        }
        if ($links !== '' && $links !== '…') {
            $canvas->text($rand, $y, $links, $schrift, $groesse, $farbe);
        }
    });
}
$dompdf->stream(Export::dateiname($gruppe['name'], 'pdf'), ['Attachment' => true]);
