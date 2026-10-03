<?php
declare(strict_types=1);

// Gruppenexport: Excel-Tabelle (format=xlsx) bzw. Bilder-ZIP (format=bilder).
// Das PDF liefert export_gruppe_pdf.php.

require __DIR__ . '/upload_pruefung.php';
require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Export;
use App\GruppeRepository;
use App\Helpers;
use App\Protokoll;
use App\WerkRepository;

$benutzer = Auth::requireLogin();
$id = (int) ($_GET['id'] ?? 0);
$gruppe = GruppeRepository::neu()->finde($id);
if ($gruppe === null || !Auth::darfGruppeSehen($benutzer, $id)) {
    Helpers::abbrechen(404, 'Diese Gruppe wurde nicht gefunden.');
}
$format = ($_GET['format'] ?? '') === 'bilder' ? 'bilder' : 'xlsx';

Protokoll::schreibe('export_gruppe', ($format === 'bilder' ? 'Bilder-ZIP' : 'Excel') . " „{$gruppe['name']}“");
session_write_close();
@set_time_limit(0);

$werke = WerkRepository::neu()->fuerGruppe($id);
if ($format === 'bilder') {
    Export::sendeBilderZip($werke, Export::dateiname($gruppe['name'] . '_bilder', 'zip'));
} else {
    Export::sendeTabelle($werke, 'xlsx', $gruppe['name']);
}
