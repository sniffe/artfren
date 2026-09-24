<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Bilder;
use App\Export;
use App\GruppeRepository;
use App\Helpers;
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

Protokoll::schreibe('export_gruppe', "PDF „{$gruppe['name']}“");
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
