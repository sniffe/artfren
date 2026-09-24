<?php
declare(strict_types=1);

namespace App;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/** Gemeinsame Export-Logik für CSV und XLSX. */
final class Export
{
    /** DB-Feld => Spaltenüberschrift */
    public const SPALTEN = [
        'ort' => 'Ort',
        'maler' => 'Maler',
        'titel' => 'Titel',
        'format' => 'Format',
        'technik' => 'Technik',
        'entstehungsjahr' => 'Entstehungsjahr',
        'ankaufjahr' => 'Ankaufjahr',
        'ankauf' => 'Ankauf',
        'ankaufswert' => 'Ankaufswert',
        'wert' => 'Wert',
        'werktyp' => 'Bild',
        'status_farbe' => 'Status',
        'bild_dateiname' => 'Dateiname',
    ];

    private const ZAHLEN = ['entstehungsjahr', 'ankaufjahr', 'ankaufswert', 'wert'];

    /** @return array<int, string|int|float|null> */
    private static function werte(array $werk): array
    {
        $zeile = [];
        foreach (array_keys(self::SPALTEN) as $feld) {
            $wert = $werk[$feld] ?? null;
            if ($feld === 'status_farbe') {
                $wert = $wert !== null ? Helpers::statusLabel($wert) : null;
            } elseif (in_array($feld, self::ZAHLEN, true) && $wert !== null) {
                $wert = str_contains((string) $wert, '.') ? (float) $wert : (int) $wert;
            }
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
        fputcsv($handle, array_values(self::SPALTEN), ';', '"', '');
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
        foreach (self::SPALTEN as $ueberschrift) {
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

        foreach (range(1, count(self::SPALTEN)) as $i) {
            $blatt->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $blatt->freezePane('A2');

        return $mappe;
    }

    public static function dateiname(string $basis, string $endung): string
    {
        $basis = preg_replace('/[^A-Za-z0-9_-]+/', '_', $basis) ?: 'export';
        return trim($basis, '_') . '_' . date('Y-m-d_Hi') . '.' . $endung;
    }
}
