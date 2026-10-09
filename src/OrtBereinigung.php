<?php
declare(strict_types=1);

namespace App;

/** Findet Tippvarianten von Ortsnamen und führt sie zusammen. */
final class OrtBereinigung extends NamenBereinigung
{
    public static function neu(): self
    {
        return new self(Database::get());
    }

    protected function spalte(): string
    {
        return 'ort';
    }

    protected function aliasTabelle(): string
    {
        return 'ort_alias';
    }

    /** @return array<string, int> Ort => Anzahl Werke */
    public function orte(): array
    {
        return $this->namen();
    }

    /**
     * Zwei Namen gelten als ähnlich, wenn sie sich um höchstens zwei Buchstaben
     * unterscheiden UND dieselben Zahlen enthalten – "Top 17" und "Top 18" sind
     * verschiedene Orte, "Top 17" und "Tio 17" vermutlich ein Tippfehler.
     */
    public static function aehnlich(string $a, string $b): bool
    {
        $kurzA = self::vergleichsform($a);
        $kurzB = self::vergleichsform($b);
        if ($kurzA === $kurzB) {
            return true;
        }
        preg_match_all('/\d+/', $a, $zahlenA);
        preg_match_all('/\d+/', $b, $zahlenB);
        if ($zahlenA[0] !== $zahlenB[0]) {
            return false;
        }
        if (min(strlen($kurzA), strlen($kurzB)) < 6) {
            return false;
        }
        return levenshtein($kurzA, $kurzB) <= 2;
    }
}
