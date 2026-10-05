<?php
declare(strict_types=1);

namespace App;

use App\Felder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ZipStream\CompressionMethod;
use ZipStream\ZipStream;

/** Gemeinsame Export-Logik: Tabelle (XLSX/CSV) und Bilder-ZIP. */
final class Export
{
    /**
     * Exportierbare Felder in der historisch fixierten Reihenfolge.
     * Abgeleitet von Felder::exportSpalten() – nie mehr direkt pflegen.
     * @return array<string, string>
     */
    private static function spalten(): array
    {
        return Felder::exportSpalten();
    }

    /**
     * Felder, die im Export als Zahl (nicht als String) gesetzt werden.
     * @return string[]
     */
    private static function zahlenFelder(): array
    {
        return Felder::zahlenKeys();
    }

    /** @return array<int, string|int|float|null> */
    private static function werte(array $werk): array
    {
        $zahlen = self::zahlenFelder();
        $zeile = [];
        foreach (array_keys(self::spalten()) as $feld) {
            $wert = $werk[$feld] ?? null;
            if ($feld === 'status_farbe') {
                $wert = $wert !== null ? Helpers::statusLabel($wert) : null;
            } elseif ($feld === 'web_freigabe') {
                $wert = (int) $wert === 1 ? 'ja' : 'nein';
            } elseif (in_array($feld, $zahlen, true) && $wert !== null) {
                $wert = str_contains((string) $wert, '.') ? (float) $wert : (int) $wert;
            }
            // bild_dateiname_2..8 sind null wenn aus einer Abfrage ohne Extra-Bilder
            $zeile[] = $wert;
        }
        return $zeile;
    }

    /**
     * Excel/LibreOffice führen Zellen, die mit = + - @ beginnen, als Formel
     * aus. Ein vorangestelltes Hochkomma macht sie zu reinem Text.
     */
    public static function csvSicher(string|int|float|null $wert): string|int|float|null
    {
        if (is_string($wert) && $wert !== '' && in_array($wert[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $wert;
        }
        return $wert;
    }

    /** @param iterable<array> $werke */
    public static function schreibeCsv($handle, iterable $werke): void
    {
        fwrite($handle, "\xEF\xBB\xBF"); // BOM, damit Excel UTF-8 erkennt
        fputcsv($handle, array_values(self::spalten()), ';', '"', '');
        foreach ($werke as $werk) {
            fputcsv($handle, array_map([self::class, 'csvSicher'], self::werte($werk)), ';', '"', '');
        }
    }

    /** @param iterable<array> $werke */
    public static function xlsx(iterable $werke): Spreadsheet
    {
        $mappe = new Spreadsheet();
        $blatt = $mappe->getActiveSheet();
        $blatt->setTitle('Kunstwerke');

        $spalte = 1;
        foreach (self::spalten() as $ueberschrift) {
            $blatt->setCellValue(Coordinate::stringFromColumnIndex($spalte++) . '1', $ueberschrift);
        }
        $blatt->getStyle('1:1')->getFont()->setBold(true);

        $zeile = 2;
        foreach ($werke as $werk) {
            $spalte = 1;
            foreach (self::werte($werk) as $wert) {
                $zelle = Coordinate::stringFromColumnIndex($spalte++) . $zeile;
                if ($wert === null) {
                    continue;
                }
                // Text ausdrücklich als Text setzen: sonst würde "=…" als Formel angelegt.
                if (is_string($wert)) {
                    $blatt->setCellValueExplicit($zelle, $wert, DataType::TYPE_STRING);
                } else {
                    $blatt->setCellValue($zelle, $wert);
                }
            }
            $zeile++;
        }

        foreach (range(1, count(self::spalten())) as $i) {
            $blatt->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $blatt->freezePane('A2');

        return $mappe;
    }

    /**
     * Dateinamen der Bilder, die zu den Werken gehören und auf dem Server
     * liegen – genau so, wie sie in der Spalte "Dateiname" stehen.
     *
     * @param iterable<array> $werke
     * @return array{dateien: string[], fehlend: string[], groesse: int}
     */
    public static function bilderZuWerken(iterable $werke): array
    {
        $dateien = [];
        $fehlend = [];
        $groesse = 0;
        foreach ($werke as $werk) {
            // Hauptbild + eventuelle Extra-Bilder aus dem Export-Query
            $kandidaten = [$werk['bild_dateiname'] ?? null];
            for ($n = 2; $n <= 8; $n++) {
                $kandidaten[] = $werk["bild_dateiname_{$n}"] ?? null;
            }
            foreach ($kandidaten as $name) {
                if (!$name || isset($dateien[$name]) || isset($fehlend[$name])) {
                    continue;
                }
                $pfad = Bilder::originalPfad($name);
                if (is_file($pfad)) {
                    $dateien[$name] = $name;
                    $groesse += (int) filesize($pfad);
                } else {
                    $fehlend[$name] = $name;
                }
            }
        }
        return ['dateien' => array_values($dateien), 'fehlend' => array_values($fehlend), 'groesse' => $groesse];
    }

    /**
     * Streamt die Bilder als ZIP direkt an den Browser – ohne Zwischendatei,
     * damit auch große Sammlungen auf Shared-Hosting nicht am Speicherplatz
     * oder an der Laufzeit scheitern. Die Dateien liegen flach im Archiv, mit
     * denselben Namen wie in der Spalte "Dateiname" der Excel-Datei: der Inhalt
     * kann unverändert unter "Import → Bilder hochladen" (oder per FTP in
     * bilder/) wieder eingespielt werden.
     *
     * @param iterable<array> $werke
     */
    public static function sendeBilderZip(iterable $werke, string $zipName): void
    {
        $bilder = self::bilderZuWerken($werke);
        $zip = new ZipStream(
            outputName: $zipName,
            sendHttpHeaders: true,
            contentType: 'application/zip',
            // Größen vorab in die Kopfdaten schreiben: kompatibler mit dem
            // Entpacken unter Windows/macOS als "Zero-Header"-Archive.
            defaultEnableZeroHeader: false,
            // Bilder sind bereits komprimiert – nur speichern spart Rechenzeit.
            defaultCompressionMethod: CompressionMethod::STORE,
        );
        foreach ($bilder['dateien'] as $name) {
            $zip->addFileFromPath(fileName: $name, path: Bilder::originalPfad($name));
        }
        if ($bilder['fehlend'] !== []) {
            $zip->addFile(
                fileName: 'FEHLENDE_BILDER.txt',
                data: "Diese Dateien stehen in der Excel-Spalte \"Dateiname\", liegen aber nicht auf dem Server:\r\n\r\n"
                    . implode("\r\n", $bilder['fehlend']) . "\r\n",
                compressionMethod: CompressionMethod::DEFLATE,
            );
        }
        $zip->finish();
    }

    /** Sendet die Werke als Excel- oder CSV-Download. @param iterable<array> $werke */
    public static function sendeTabelle(iterable $werke, string $format, string $basisname): void
    {
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . self::dateiname($basisname, 'csv') . '"');
            $ausgabe = fopen('php://output', 'w');
            self::schreibeCsv($ausgabe, $werke);
            fclose($ausgabe);
            return;
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . self::dateiname($basisname, 'xlsx') . '"');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx(self::xlsx($werke)))->save('php://output');
    }

    public static function dateiname(string $basis, string $endung): string
    {
        $basis = preg_replace('/[^A-Za-z0-9_-]+/', '_', $basis) ?: 'export';
        return trim($basis, '_') . '_' . date('Y-m-d_Hi') . '.' . $endung;
    }
}
