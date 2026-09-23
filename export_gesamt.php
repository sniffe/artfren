<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Auth;
use App\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$aktuellerBenutzer = Auth::requireAdmin();
$format = (string) ($_GET['format'] ?? '');

if ($format === '') {
    render('export_gesamt', ['titel' => 'Gesamtexport', 'aktuelleSeite' => '']);
    exit;
}

$pdo = Database::get();
$spalten = ['ort', 'maler', 'titel', 'format', 'technik', 'entstehungsjahr', 'ankaufjahr', 'ankauf', 'ankaufswert', 'wert', 'werktyp', 'status_farbe'];
$kopfzeile = ['Ort', 'Maler', 'Titel', 'Format', 'Technik', 'Entstehungsjahr', 'Ankaufjahr', 'Ankauf', 'Ankaufswert', 'Wert', 'Typ', 'Status-Farbe', 'Bild-Dateiname'];

$stmt = $pdo->query(
    "SELECT k.*, (
        SELECT dateiname FROM bilder b WHERE b.kunstwerk_id = k.id
        ORDER BY ist_hauptbild DESC, sortierung ASC LIMIT 1
    ) AS bild_dateiname
    FROM kunstwerke k ORDER BY k.ort, k.maler, k.titel"
);
$zeitstempel = date('Y-m-d_Hi');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="kunstwerke_gesamt_' . $zeitstempel . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM für Excel
    fputcsv($out, $kopfzeile, ';');
    foreach ($stmt as $row) {
        $zeile = [];
        foreach ($spalten as $s) {
            $zeile[] = $row[$s];
        }
        $zeile[] = $row['bild_dateiname'];
        fputcsv($out, $zeile, ';');
    }
    fclose($out);
    exit;
}

if ($format === 'xlsx') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Kunstwerke');
    $sheet->fromArray($kopfzeile, null, 'A1');

    $zeile = 2;
    foreach ($stmt as $row) {
        $werte = [];
        foreach ($spalten as $s) {
            $werte[] = $row[$s];
        }
        $werte[] = $row['bild_dateiname'];
        $sheet->fromArray($werte, null, 'A' . $zeile);
        $zeile++;
    }
    foreach (range('A', 'M') as $spalteBuchstabe) {
        $sheet->getColumnDimension($spalteBuchstabe)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="kunstwerke_gesamt_' . $zeitstempel . '.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

http_response_code(400);
exit('Unbekanntes Format.');
