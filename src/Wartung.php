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
     * "abweichend" sind Dateien, die vorhanden sind, deren Inhalt aber nicht
     * zur Version passt: abgebrochener Upload, Datei einer älteren Version
     * oder von Hand auf dem Server geändert. Ältere Dateilisten ohne
     * Prüfsummen (nur Pfade) prüfen weiterhin nur die Vollständigkeit.
     *
     * @return array{gesamt: int, fehlend: string[], abweichend: string[]}|null
     */
    public static function fehlendeProgrammdateien(): ?array
    {
        $liste = @file(APP_ROOT . '/' . self::DATEILISTE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($liste === false) {
            return null;
        }
        $fehlend = [];
        $abweichend = [];
        foreach ($liste as $zeile) {
            [$soll, $pfad] = self::zerlegeZeile($zeile);
            $datei = APP_ROOT . '/' . $pfad;
            if (!is_file($datei)) {
                $fehlend[] = $pfad;
            } elseif ($soll !== null && self::pruefsumme($datei) !== $soll) {
                $abweichend[] = $pfad;
            }
        }
        return ['gesamt' => count($liste), 'fehlend' => $fehlend, 'abweichend' => $abweichend];
    }

    /**
     * Prüfsumme einer Programmdatei. Zeilenenden werden vereinheitlicht, damit
     * ein FTP-Programm im Textmodus (CRLF statt LF) keinen Fehlalarm auslöst.
     */
    public static function pruefsumme(string $datei): ?string
    {
        $inhalt = @file_get_contents($datei);
        if ($inhalt === false) {
            return null;
        }
        return hash('sha256', str_replace("\r\n", "\n", $inhalt));
    }

    /**
     * Inhalt der Dateiliste für die angegebenen Pfade (relativ zum
     * Programmordner): je Zeile "Prüfsumme  Pfad", sortiert. Dokumentation,
     * Composer-Dateien, CLI-Skripte und versteckte Dateien gehören nicht dazu.
     *
     * @param string[] $pfade
     */
    public static function erzeugeDateiliste(array $pfade): string
    {
        $pfade = array_values(array_filter($pfade, static fn(string $pfad): bool =>
            $pfad !== self::DATEILISTE
            && !in_array($pfad, ['composer.json', 'composer.lock'], true)
            && !str_ends_with($pfad, '.md')
            && !str_starts_with($pfad, 'scripts/')
            && !preg_match('~(^|/)\.~', $pfad)
        ));
        sort($pfade, SORT_STRING);
        $zeilen = [];
        foreach ($pfade as $pfad) {
            $zeilen[] = (self::pruefsumme(APP_ROOT . '/' . $pfad) ?? '-') . '  ' . $pfad;
        }
        return implode("\n", $zeilen) . "\n";
    }

    /** @return array{0: ?string, 1: string} [Prüfsumme oder null, Pfad] */
    private static function zerlegeZeile(string $zeile): array
    {
        if (preg_match('/^([0-9a-f]{64})  (.+)$/', $zeile, $m)) {
            return [$m[1], $m[2]];
        }
        return [null, trim($zeile)];
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
