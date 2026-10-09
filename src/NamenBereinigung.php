<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Gemeinsame Grundlage für "Orte bereinigen" und "Künstler bereinigen":
 * findet Schreibvarianten in einer Spalte der Werke, führt sie zusammen und
 * merkt sich die alten Schreibweisen in einer Alias-Tabelle, damit ein
 * späterer Import der unveränderten Excel-Datei sie wieder richtig zuordnet.
 */
abstract class NamenBereinigung
{
    public function __construct(protected readonly PDO $pdo)
    {
    }

    /** Spalte in kunstwerke, z. B. "ort" oder "maler". */
    abstract protected function spalte(): string;

    /** Tabelle mit alias (normalisiert) → ziel (kanonischer Name). */
    abstract protected function aliasTabelle(): string;

    /** Ob zwei Schreibweisen vermutlich denselben Namen meinen. */
    abstract public static function aehnlich(string $a, string $b): bool;

    /** Normalisierter Schlüssel einer Schreibweise (wie beim Import-Abgleich). */
    public static function schluessel(?string $name): string
    {
        return Helpers::ortSchluessel($name);
    }

    /** @return array<string, int> Name => Anzahl Werke */
    public function namen(): array
    {
        $s = $this->spalte();
        return $this->pdo->query(
            "SELECT {$s}, COUNT(*) FROM kunstwerke WHERE {$s} IS NOT NULL AND {$s} <> '' AND geloescht_am IS NULL GROUP BY {$s} ORDER BY {$s} COLLATE NOCASE"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Gruppen ähnlicher Schreibweisen (transitiv verbunden).
     *
     * @return array<int, array<string, int>> je Gruppe: Name => Anzahl, häufigster zuerst
     */
    public function vorschlaege(): array
    {
        $alle = $this->namen();
        $namen = array_map('strval', array_keys($alle));
        $eltern = array_combine($namen, $namen);

        $finde = static function (string $x) use (&$eltern, &$finde): string {
            return $eltern[$x] === $x ? $x : ($eltern[$x] = $finde($eltern[$x]));
        };

        $anzahl = count($namen);
        for ($i = 0; $i < $anzahl; $i++) {
            for ($j = $i + 1; $j < $anzahl; $j++) {
                if (static::aehnlich($namen[$i], $namen[$j])) {
                    $eltern[$finde($namen[$j])] = $finde($namen[$i]);
                }
            }
        }

        $gruppen = [];
        foreach ($namen as $name) {
            $gruppen[$finde($name)][$name] = $alle[$name];
        }
        $gruppen = array_values(array_filter($gruppen, static fn(array $g) => count($g) > 1));
        foreach ($gruppen as &$gruppe) {
            arsort($gruppe);
        }
        return $gruppen;
    }

    /** Kleinbuchstaben ohne Leer-/Satzzeichen, Umlaute ausgeschrieben (levenshtein arbeitet bytebasiert). */
    protected static function vergleichsform(string $s): string
    {
        $s = str_replace(['ä', 'ö', 'ü', 'ß', 'é', 'è', 'á', 'à', 'ó', 'í', 'č', 'š', 'ž'], ['ae', 'oe', 'ue', 'ss', 'e', 'e', 'a', 'a', 'o', 'i', 'c', 's', 'z'], mb_strtolower($s));
        return (string) preg_replace('/[^a-z0-9]/', '', $s);
    }

    /**
     * Setzt alle Werke der Quell-Schreibweisen auf den Zielnamen und merkt sich
     * die alten Schreibweisen für künftige Importe.
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
        $s = $this->spalte();
        $t = $this->aliasTabelle();
        $zielSchluessel = static::schluessel($ziel);
        $geaendert = 0;

        $this->pdo->beginTransaction();
        $werke = $this->pdo->prepare("UPDATE kunstwerke SET {$s} = :ziel WHERE {$s} = :quelle");
        $alias = $this->pdo->prepare("INSERT OR REPLACE INTO {$t} (alias, ziel) VALUES (:alias, :ziel)");
        $kette = $this->pdo->prepare("UPDATE {$t} SET ziel = :ziel WHERE ziel = :quelle");

        foreach (array_unique($quellen) as $quelle) {
            if ($quelle === $ziel) {
                continue;
            }
            $werke->execute(['ziel' => $ziel, 'quelle' => $quelle]);
            $geaendert += $werke->rowCount();
            $kette->execute(['ziel' => $ziel, 'quelle' => $quelle]);
            if (static::schluessel($quelle) !== $zielSchluessel) {
                $alias->execute(['alias' => static::schluessel($quelle), 'ziel' => $ziel]);
            }
        }
        // Der Zielname selbst darf nie auf etwas anderes umgeleitet werden.
        $this->pdo->prepare("DELETE FROM {$t} WHERE alias = :a")->execute(['a' => $zielSchluessel]);
        $this->pdo->commit();

        return $geaendert;
    }

    /** @return array<string, string> alias => ziel */
    public function aliase(): array
    {
        return $this->pdo->query("SELECT alias, ziel FROM {$this->aliasTabelle()} ORDER BY ziel, alias")->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function aliasLoeschen(string $alias): void
    {
        $this->pdo->prepare("DELETE FROM {$this->aliasTabelle()} WHERE alias = :a")->execute(['a' => $alias]);
    }

    /**
     * Ersetzt in Importzeilen gemerkte alte Schreibweisen durch den Zielnamen.
     *
     * @param array<int, array{daten: array<string, mixed>}> $zeilen
     * @return int Anzahl ersetzter Werte
     */
    public function wendeAliaseAn(array &$zeilen): int
    {
        $s = $this->spalte();
        $aliase = $this->aliase();
        $ersetzt = 0;
        foreach ($zeilen as &$eintrag) {
            $wert = $eintrag['daten'][$s] ?? null;
            $schluessel = static::schluessel($wert);
            if (isset($aliase[$schluessel]) && $aliase[$schluessel] !== $wert) {
                $eintrag['daten'][$s] = $aliase[$schluessel];
                $ersetzt++;
            }
        }
        return $ersetzt;
    }
}
