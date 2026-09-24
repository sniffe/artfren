<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Export;
use App\Protokoll;
use App\WerkRepository;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

Auth::requireAdmin();
$format = (string) ($_GET['format'] ?? '');

if (!in_array($format, ['csv', 'xlsx'], true)) {
    render('export_gesamt', ['titel' => 'Gesamtexport', 'aktuelleSeite' => 'export']);
    exit;
}

Protokoll::schreibe('export_gesamt', strtoupper($format));
session_write_close();
@set_time_limit(300);
$werke = WerkRepository::neu()->alle();

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . Export::dateiname('kunstwerke_gesamt', 'csv') . '"');
    $ausgabe = fopen('php://output', 'w');
    Export::schreibeCsv($ausgabe, $werke);
    fclose($ausgabe);
    exit;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . Export::dateiname('kunstwerke_gesamt', 'xlsx') . '"');
(new Xlsx(Export::xlsx($werke)))->save('php://output');
