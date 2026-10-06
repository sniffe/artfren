<?php
declare(strict_types=1);

namespace App;

/**
 * Prüft nach einem Update per Hochladen, ob alles angekommen ist, und
 * entfernt Dateien früherer Versionen, die das Hochladen nicht löscht.
 */
final class Wartung
{
    /** Ordner, die eine .htaccess-Sperre mitbringen ('' = Hauptordner). */
    private const GESCHUETZTE_ORDNER = ['', 'data', 'backups', 'bilder', 'src', 'templates', 'migrations', 'scripts', 'vendor'];

    /** Liste aller Programmdateien der Version, erzeugt mit scripts/dateiliste.php. */
    public const DATEILISTE = 'src/dateiliste.txt';

    /**
     * .htaccess-Dateien, die fehlen – typischerweise, weil das FTP-Programm
     * versteckte Dateien (Name beginnt mit Punkt) nicht hochgeladen hat.
     *
     * @return string[]
     */
    public static function fehlendeSchutzdateien(): array
    {
        $fehlend = [];
        foreach (self::GESCHUETZTE_ORDNER as $ordner) {
            $verzeichnis = APP_ROOT . ($ordner === '' ? '' : '/' . $ordner);
            if (is_dir($verzeichnis) && !is_file($verzeichnis . '/.htaccess')) {
                $fehlend[] = ($ordner === '' ? '' : $ordner . '/') . '.htaccess';
            }
        }
        return $fehlend;
    }

    /**
     * Vergleicht die Programmdateien auf dem Server mit der Dateiliste der
     * Version. Null, wenn die Dateiliste selbst fehlt.
     *
     * @return array{gesamt: int, fehlend: string[]}|null
     */
    public static function fehlendeProgrammdateien(): ?array
    {
        $liste = @file(APP_ROOT . '/' . self::DATEILISTE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($liste === false) {
            return null;
        }
        $fehlend = [];
        foreach ($liste as $pfad) {
            if (!is_file(APP_ROOT . '/' . $pfad)) {
                $fehlend[] = $pfad;
            }
        }
        return ['gesamt' => count($liste), 'fehlend' => $fehlend];
    }

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
