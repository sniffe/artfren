<?php
declare(strict_types=1);

namespace App;

/**
 * Erkennt Dateien früherer Versionen, die ein Update per Hochladen nicht
 * entfernt, und löscht sie auf Knopfdruck.
 */
final class Wartung
{
    /**
     * Pfade relativ zum Programmordner, die es in der aktuellen Version nicht
     * mehr gibt. Bei künftigen Versionen hier ergänzen.
     */
    private const VERALTET = [
        'thumbs' => 'Vorschaubild-Cache ohne Zugriffsschutz (jetzt in data/cache)',
        'thumb.php' => 'alte Vorschaubild-Auslieferung (jetzt bild.php)',
        'export_gruppe_zip.php' => 'alter Gruppenexport (jetzt Excel + Bilder-ZIP)',
        'migrations/schema.sql' => 'altes Datenbankschema (jetzt nummerierte Migrationen)',
        'src/CsvImport.php' => 'alter CSV-Import (jetzt TabellenImport)',
    ];

    /** @return array<string, string> vorhandene veraltete Pfade => Beschreibung */
    public static function veralteteDateien(): array
    {
        $gefunden = [];
        foreach (self::VERALTET as $pfad => $beschreibung) {
            if (file_exists(APP_ROOT . '/' . $pfad) || is_link(APP_ROOT . '/' . $pfad)) {
                $gefunden[$pfad] = $beschreibung;
            }
        }
        return $gefunden;
    }

    /**
     * Löscht ausschließlich die fest hinterlegten Pfade.
     *
     * @return array{geloescht: string[], fehler: string[]}
     */
    public static function loescheVeraltete(): array
    {
        $ergebnis = ['geloescht' => [], 'fehler' => []];
        foreach (array_keys(self::veralteteDateien()) as $pfad) {
            if (self::loesche(APP_ROOT . '/' . $pfad)) {
                $ergebnis['geloescht'][] = $pfad;
            } else {
                $ergebnis['fehler'][] = $pfad;
            }
        }
        return $ergebnis;
    }

    private static function loesche(string $pfad): bool
    {
        // Symbolische Links nur selbst entfernen, nie ihrem Ziel folgen.
        if (is_link($pfad) || is_file($pfad)) {
            return @unlink($pfad);
        }
        if (!is_dir($pfad)) {
            return true;
        }
        foreach (new \FilesystemIterator($pfad, \FilesystemIterator::SKIP_DOTS) as $eintrag) {
            if (!self::loesche($eintrag->getPathname())) {
                return false;
            }
        }
        return @rmdir($pfad);
    }
}
