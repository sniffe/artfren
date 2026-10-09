<?php
declare(strict_types=1);

namespace App;

/**
 * Findet Schreibvarianten von Künstlernamen und führt sie zusammen
 * (Spalte "maler", in der Oberfläche "Künstler").
 *
 * Felder mit mehreren Künstlern ("Angeli Eduard, Ringel Franz") werden nicht
 * aufgeteilt, sondern als eigener Eintrag behandelt.
 */
final class KuenstlerBereinigung extends NamenBereinigung
{
    public static function neu(): self
    {
        return new self(Database::get());
    }

    protected function spalte(): string
    {
        return 'maler';
    }

    protected function aliasTabelle(): string
    {
        return 'maler_alias';
    }

    /**
     * Ähnlich, wenn
     * - nur Groß-/Kleinschreibung, Leer- oder Satzzeichen, Umlaute abweichen
     *   ("Hermanky Gerhard" / "hermanky, Gerhard"),
     * - dieselben Namensteile in anderer Reihenfolge stehen
     *   ("Baldinger Peter" / "Peter Baldinger"), oder
     * - (bei gleichen Zahlen im Namen)
     * - sich die Namen um höchstens zwei Buchstaben unterscheiden
     *   ("Herzmanovsky" / "Herzmanowsky"), bei längeren Namen (ab 12 Zeichen)
     *   um höchstens drei.
     */
    public static function aehnlich(string $a, string $b): bool
    {
        $kurzA = self::vergleichsform($a);
        $kurzB = self::vergleichsform($b);
        if ($kurzA === $kurzB) {
            return true;
        }
        $teileA = self::namensteile($a);
        $teileB = self::namensteile($b);
        if ($teileA !== [] && $teileA === $teileB) {
            return true;
        }
        // Unterschiedliche Zahlen ("Meister 1" / "Meister 2") sind nie ein Tippfehler.
        preg_match_all('/\d+/', $a, $zahlenA);
        preg_match_all('/\d+/', $b, $zahlenB);
        if ($zahlenA[0] !== $zahlenB[0]) {
            return false;
        }
        $kuerzer = min(strlen($kurzA), strlen($kurzB));
        if ($kuerzer < 6) {
            return false;
        }
        $grenze = $kuerzer >= 12 ? 3 : 2;
        if (levenshtein($kurzA, $kurzB) <= $grenze) {
            return true;
        }
        // Vertauschte Reihenfolge mit Tippfehler: sortierte Teile vergleichen.
        return levenshtein(implode('', $teileA), implode('', $teileB)) <= $grenze;
    }

    /** @return string[] normalisierte Namensteile, sortiert */
    private static function namensteile(string $name): array
    {
        $teile = preg_split('/[\s,;\/]+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $teile = array_values(array_filter(array_map(static fn(string $t): string => self::vergleichsform($t), $teile)));
        sort($teile, SORT_STRING);
        return $teile;
    }
}
