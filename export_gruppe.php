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
$istAdmin = Auth::isAdmin($benutzer);

// Berechtigungsprüfung für eingeschränkte Benutzer
if (!$istAdmin) {
    if ($format === 'bilder' && !($benutzer['darf_bilder_export'] ?? 0)) {
        Protokoll::schreibe('export_verweigert', “Bilder-ZIP „{$gruppe['name']}””);
        Helpers::abbrechen(403, t('export.keine_rechte'));
    }
    if ($format === 'xlsx' && !($benutzer['darf_excel'] ?? 0)) {
        Protokoll::schreibe('export_verweigert', “Excel „{$gruppe['name']}””);
        Helpers::abbrechen(403, t('export.keine_rechte'));
    }
}

Protokoll::schreibe('export_gruppe', ($format === 'bilder' ? 'Bilder-ZIP' : 'Excel') . “ „{$gruppe['name']}””);
session_write_close();
@set_time_limit(0);

$repo = WerkRepository::neu();
$werke = $repo->fuerGruppeExport($id);
if ($format === 'bilder') {
    Export::sendeBilderZip($werke, Export::dateiname($gruppe['name'] . '_bilder', 'zip'));
} else {
    Export::sendeTabelle($werke, 'xlsx', $gruppe['name']);
}
