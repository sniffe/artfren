<?php
declare(strict_types=1);

namespace App;

use PDO;

/** Findet Tippvarianten von Ortsnamen und führt sie zusammen. */
final class OrtBereinigung
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public static function neu(): self
    {
        return new self(Database::get());
    }

    /** @return array<string, int> Ort => Anzahl Werke */
    public function orte(): array
    {
        return $this->pdo->query(
            "SELECT ort, COUNT(*) FROM kunstwerke WHERE ort IS NOT NULL AND ort <> '' GROUP BY ort ORDER BY ort COLLATE NOCASE"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Gruppen ähnlicher Schreibweisen. Zwei Namen gelten als ähnlich, wenn sie
     * sich um höchstens zwei Buchstaben unterscheiden UND dieselben Zahlen
     * enthalten – "Top 17" und "Top 18" sind verschiedene Orte, "Top 17" und
     * "Tio 17" vermutlich ein Tippfehler.
     *
     * @return array<int, array<string, int>> je Gruppe: Ort => Anzahl, häufigster zuerst
     */
    public function vorschlaege(): array
    {
        $orte = $this->orte();
        $namen = array_keys($orte);
        $eltern = array_combine($namen, $namen);

        $finde = static function (string $x) use (&$eltern, &$finde): string {
            return $eltern[$x] === $x ? $x : ($eltern[$x] = $finde($eltern[$x]));
        };

        $anzahl = count($namen);
        for ($i = 0; $i < $anzahl; $i++) {
            for ($j = $i + 1; $j < $anzahl; $j++) {
                if (self::aehnlich($namen[$i], $namen[$j])) {
                    $eltern[$finde($namen[$j])] = $finde($namen[$i]);
                }
            }
        }

        $gruppen = [];
        foreach ($namen as $name) {
            $gruppen[$finde($name)][$name] = $orte[$name];
        }
        $gruppen = array_values(array_filter($gruppen, static fn(array $g) => count($g) > 1));
        foreach ($gruppen as &$gruppe) {
            arsort($gruppe);
        }
        return $gruppen;
    }

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

    /** Kleinbuchstaben ohne Leer-/Satzzeichen, Umlaute ausgeschrieben (levenshtein arbeitet bytebasiert). */
    private static function vergleichsform(string $s): string
    {
        $s = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], mb_strtolower($s));
        return (string) preg_replace('/[^a-z0-9]/', '', $s);
    }

    /**
     * Setzt alle Werke der Quell-Orte auf den Zielnamen und merkt sich die
     * alten Schreibweisen, damit ein späterer Re-Import sie wieder zuordnet.
     *
     * @param string[] $quellen
     * @return int Anzahl geänderter Werke
     */
    public function zusammenfuehren(array $quellen, string $ziel): int
    {
        $ziel = trim((string) preg_replace('/\s+/u', ' ', $ziel));
        if ($ziel === '') {
            return 0;
        }
        $zielSchluessel = Helpers::ortSchluessel($ziel);
        $geaendert = 0;

        $this->pdo->beginTransaction();
        $werke = $this->pdo->prepare('UPDATE kunstwerke SET ort = :ziel WHERE ort = :quelle');
        $alias = $this->pdo->prepare('INSERT OR REPLACE INTO ort_alias (alias, ziel) VALUES (:alias, :ziel)');
        $kette = $this->pdo->prepare('UPDATE ort_alias SET ziel = :ziel WHERE ziel = :quelle');

        foreach (array_unique($quellen) as $quelle) {
            if ($quelle === $ziel) {
                continue;
            }
            $werke->execute(['ziel' => $ziel, 'quelle' => $quelle]);
            $geaendert += $werke->rowCount();
            $kette->execute(['ziel' => $ziel, 'quelle' => $quelle]);
            if (Helpers::ortSchluessel($quelle) !== $zielSchluessel) {
                $alias->execute(['alias' => Helpers::ortSchluessel($quelle), 'ziel' => $ziel]);
            }
        }
        // Der Zielname selbst darf nie auf etwas anderes umgeleitet werden.
        $this->pdo->prepare('DELETE FROM ort_alias WHERE alias = :a')->execute(['a' => $zielSchluessel]);
        $this->pdo->commit();

        return $geaendert;
    }

    /** @return array<string, string> alias => ziel */
    public function aliase(): array
    {
        return $this->pdo->query('SELECT alias, ziel FROM ort_alias ORDER BY ziel, alias')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function aliasLoeschen(string $alias): void
    {
        $this->pdo->prepare('DELETE FROM ort_alias WHERE alias = :a')->execute(['a' => $alias]);
    }
}
