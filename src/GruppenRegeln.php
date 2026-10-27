<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Sortierung und Filterangebot einer Gruppe (gruppen.sortierung_regel, gruppen.filter_angebot)
 * und die Filterauswahl eines Betrachters.
 *
 * Einzige Quelle für Bedingungen und Reihenfolge, mit denen die Werke einer Gruppe gelistet
 * werden: Gruppenansicht, Summen, PDF, Excel/ZIP, öffentliche Galerie und Zählungen.
 *
 * - Die Sortierung legt der Admin je Gruppe fest (von Hand oder nach Feldern).
 * - Der Admin legt fest, welche Filter angeboten werden. Betrachter (Admins, eingeschränkte
 *   Benutzer, Galerie-Gäste) wählen selbst; die Auswahl kommt aus der Adresse (f_*-Parameter),
 *   wird nie gespeichert und schränkt nur ein, was der Betrachter ohnehin sehen darf.
 * - Der Ankaufswert ist nie Sortier- oder Filterfeld. In der Galerie wird ein Filter nur
 *   angeboten, wenn das Feld dort sichtbar ist (sonst verriete er verborgene Angaben).
 *
 * Spaltennamen stammen nur aus den Whitelists unten, nie aus Eingaben.
 */
final class GruppenRegeln
{
    /** Erlaubte Sortierfelder. */
    public const SORTIER_FELDER = ['maler', 'titel', 'ort', 'technik', 'werktyp', 'format', 'herkunft', 'entstehungsjahr', 'ankaufjahr', 'wert'];

    /** Filter als Werteliste (ODER innerhalb der Liste). */
    public const FILTER_LISTEN = ['maler', 'technik', 'herkunft', 'ort', 'werktyp', 'format'];

    /** Filter als Bereich von/bis. */
    public const FILTER_BEREICHE = ['entstehungsjahr', 'wert'];

    /** Alle anbietbaren Filter in fester Reihenfolge; 'titel' ist die Suche „Titel enthält“. */
    public const ANGEBOT = ['maler', 'technik', 'herkunft', 'ort', 'werktyp', 'format', 'entstehungsjahr', 'wert', 'titel'];

    private const TEXT_FELDER = ['maler', 'titel', 'ort', 'technik', 'werktyp', 'format', 'herkunft'];
    private const MAX_LISTE = 200;
    private const MAX_TEXT = 200;

    /** @var array<int, array{sortierung: ?array, angebot: string[]}> Zwischenspeicher je Anfrage */
    private static array $cache = [];

    // ------------------------------------------------------------ Normalisieren

    /**
     * Sortierregel prüfen und vereinheitlichen. Annahme: Array oder JSON-Text.
     * Rückgabe null = nichts gesetzt (= Handsortierung).
     */
    public static function normalisiereSortierung(mixed $eingabe): ?array
    {
        $daten = self::alsArray($eingabe);
        if ($daten === null || ($daten['modus'] ?? '') !== 'felder') {
            return null;
        }
        $felder = [];
        foreach (array_slice((array) ($daten['felder'] ?? []), 0, 2) as $f) {
            $feld = is_array($f) ? (string) ($f['feld'] ?? '') : '';
            if (!in_array($feld, self::SORTIER_FELDER, true) || in_array($feld, array_column($felder, 'feld'), true)) {
                continue;
            }
            $felder[] = ['feld' => $feld, 'richtung' => ($f['richtung'] ?? '') === 'desc' ? 'desc' : 'asc'];
        }
        return $felder === [] ? null : ['modus' => 'felder', 'felder' => $felder];
    }

    /**
     * Filterangebot prüfen: Liste erlaubter Schlüssel in fester Reihenfolge.
     * @return string[] (leer = keine Filter angeboten)
     */
    public static function normalisiereAngebot(mixed $eingabe): array
    {
        $daten = self::alsArray($eingabe) ?? [];
        $gewaehlt = array_map('strval', array_filter($daten, 'is_scalar'));
        return array_values(array_filter(self::ANGEBOT, static fn(string $k): bool => in_array($k, $gewaehlt, true)));
    }

    /**
     * Filterauswahl prüfen und vereinheitlichen (Listen, Bereiche, titel_enthaelt).
     * Unbekannte Schlüssel fallen weg. Rückgabe null = keine Auswahl.
     */
    public static function normalisiereFilter(mixed $eingabe): ?array
    {
        $daten = self::alsArray($eingabe);
        if ($daten === null) {
            return null;
        }
        $ergebnis = [];
        foreach (self::FILTER_LISTEN as $feld) {
            $werte = [];
            foreach ((array) ($daten[$feld] ?? []) as $w) {
                if (!is_scalar($w)) {
                    continue;
                }
                $w = mb_substr(trim((string) $w), 0, self::MAX_TEXT);
                if ($w !== '' && !in_array($w, $werte, true)) {
                    $werte[] = $w;
                }
            }
            if ($werte !== []) {
                sort($werte, SORT_STRING | SORT_FLAG_CASE);
                $ergebnis[$feld] = array_slice($werte, 0, self::MAX_LISTE);
            }
        }
        foreach (self::FILTER_BEREICHE as $feld) {
            $bereich = is_array($daten[$feld] ?? null) ? $daten[$feld] : [];
            $grenzen = [];
            foreach (['von', 'bis'] as $g) {
                $w = $bereich[$g] ?? null;
                if (is_string($w)) {
                    $w = str_replace(',', '.', trim($w));
                }
                if ($w === null || $w === '' || !is_numeric($w)) {
                    continue;
                }
                $grenzen[$g] = $feld === 'entstehungsjahr' ? (int) $w : round((float) $w, 2);
            }
            if ($grenzen !== []) {
                $ergebnis[$feld] = $grenzen;
            }
        }
        $titel = is_scalar($daten['titel_enthaelt'] ?? null) ? mb_substr(trim((string) $daten['titel_enthaelt']), 0, 100) : '';
        if ($titel !== '') {
            $ergebnis['titel_enthaelt'] = $titel;
        }
        return $ergebnis === [] ? null : $ergebnis;
    }

    /**
     * Filterauswahl eines Betrachters aus der Anfrage (f_maler[], f_entstehungsjahr_von,
     * f_titel …), beschränkt auf die angebotenen Filter. Rückgabe null = nicht gefiltert.
     *
     * @param string[] $angebot
     */
    public static function filterAusAnfrage(array $anfrage, array $angebot): ?array
    {
        $roh = [];
        foreach (self::FILTER_LISTEN as $feld) {
            if (in_array($feld, $angebot, true)) {
                $roh[$feld] = (array) ($anfrage['f_' . $feld] ?? []);
            }
        }
        foreach (self::FILTER_BEREICHE as $feld) {
            if (in_array($feld, $angebot, true)) {
                $roh[$feld] = ['von' => $anfrage['f_' . $feld . '_von'] ?? '', 'bis' => $anfrage['f_' . $feld . '_bis'] ?? ''];
            }
        }
        if (in_array('titel', $angebot, true)) {
            $roh['titel_enthaelt'] = is_scalar($anfrage['f_titel'] ?? null) ? (string) $anfrage['f_titel'] : '';
        }
        return self::normalisiereFilter($roh);
    }

    /** Filterauswahl als Query-String (ohne führendes ?), z. B. für Export-Links und Vor/Zurück. */
    public static function alsQuery(?array $filter): string
    {
        if ($filter === null) {
            return '';
        }
        $teile = [];
        foreach (self::FILTER_LISTEN as $feld) {
            foreach ($filter[$feld] ?? [] as $w) {
                $teile[] = 'f_' . $feld . '[]=' . rawurlencode($w);
            }
        }
        foreach (self::FILTER_BEREICHE as $feld) {
            foreach (['von', 'bis'] as $g) {
                if (isset($filter[$feld][$g])) {
                    $teile[] = 'f_' . $feld . '_' . $g . '=' . rawurlencode((string) $filter[$feld][$g]);
                }
            }
        }
        if (($filter['titel_enthaelt'] ?? '') !== '') {
            $teile[] = 'f_titel=' . rawurlencode($filter['titel_enthaelt']);
        }
        return implode('&', $teile);
    }

    /**
     * Angebot für die Galerie: nur Filter, deren Feld dort sichtbar ist.
     * @param string[] $angebot @param string[] $sichtbareFelder (WebGruppe::erlaubteFelder)
     * @return string[]
     */
    public static function angebotFuerGalerie(array $angebot, array $sichtbareFelder): array
    {
        $oeffentlich = array_column(Felder::alle(), 'oeffentlich', 'key');
        return array_values(array_filter($angebot, static fn(string $k): bool =>
            ($oeffentlich[$k] ?? 'never') === 'immer'
            || (($oeffentlich[$k] ?? 'never') === 'waehlbar' && in_array($k, $sichtbareFelder, true))));
    }

    /**
     * Sortierung für die Galerie: Sortiert die Gruppe nach einem Feld, das die Galerie nicht
     * zeigt, verriete die Reihenfolge eine Rangfolge verborgener Angaben (z. B. Preis).
     * Dann gilt dort die Handsortierung.
     * @param string[] $sichtbareFelder (WebGruppe::erlaubteFelder)
     */
    public static function sortierungFuerGalerie(?array $sortierung, array $sichtbareFelder): ?array
    {
        if ($sortierung === null) {
            return null;
        }
        $erlaubt = self::angebotFuerGalerie(array_column($sortierung['felder'], 'feld'), $sichtbareFelder);
        return count($erlaubt) === count($sortierung['felder']) ? $sortierung : null;
    }

    // ------------------------------------------------------------ Lesen und Speichern

    /**
     * Regeln aus einer Gruppenzeile, mit Standardwerten. Ungültiges JSON gilt als
     * nicht gesetzt, wird einmal protokolliert und in der Datenbank geleert.
     *
     * @return array{sortierung: ?array, angebot: string[]}
     */
    public static function ausGruppe(array $gruppe): array
    {
        $id = (int) ($gruppe['id'] ?? 0);
        if (isset(self::$cache[$id])) {
            return self::$cache[$id];
        }
        $regeln = ['sortierung' => null, 'angebot' => []];
        foreach (['sortierung' => 'sortierung_regel', 'angebot' => 'filter_angebot'] as $art => $spalte) {
            $roh = $gruppe[$spalte] ?? null;
            if ($roh === null || $roh === '') {
                continue;
            }
            $daten = json_decode((string) $roh, true);
            if (!is_array($daten)) {
                self::verwerfeUngueltig($id, $spalte);
                continue;
            }
            $regeln[$art] = $art === 'sortierung' ? self::normalisiereSortierung($daten) : self::normalisiereAngebot($daten);
        }
        return self::$cache[$id] = $regeln;
    }

    /** Regeln einer Gruppe anhand der ID (Standardwerte, wenn es die Gruppe nicht gibt). */
    public static function fuerGruppe(PDO $pdo, int $gruppeId): array
    {
        if (isset(self::$cache[$gruppeId])) {
            return self::$cache[$gruppeId];
        }
        $stmt = $pdo->prepare('SELECT id, sortierung_regel, filter_angebot FROM gruppen WHERE id = :id');
        $stmt->execute(['id' => $gruppeId]);
        $zeile = $stmt->fetch(PDO::FETCH_ASSOC);
        return $zeile === false ? ['sortierung' => null, 'angebot' => []] : self::ausGruppe($zeile);
    }

    /** Zwischenspeicher leeren (nach dem Speichern neuer Regeln). */
    public static function vergiss(?int $gruppeId = null): void
    {
        if ($gruppeId === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$gruppeId]);
        }
    }

    /** Sortierung und Filterangebot speichern (nur normalisiertes JSON oder NULL). */
    public static function speichern(PDO $pdo, int $gruppeId, mixed $sortierung, mixed $angebot): void
    {
        $s = self::normalisiereSortierung($sortierung);
        $a = self::normalisiereAngebot($angebot);
        $pdo->prepare('UPDATE gruppen SET sortierung_regel = :s, filter_angebot = :a, geaendert_am = datetime(\'now\') WHERE id = :id')
            ->execute([
                's'  => $s === null ? null : json_encode($s, JSON_UNESCAPED_UNICODE),
                'a'  => $a === [] ? null : json_encode($a),
                'id' => $gruppeId,
            ]);
        self::vergiss($gruppeId);
    }

    // ------------------------------------------------------------ SQL

    /**
     * ORDER-BY-Ausdruck (ohne „ORDER BY“) für eine Abfrage mit kunstwerke k und
     * gruppe_kunstwerk gk.
     */
    public static function sqlOrder(?array $sortierung): string
    {
        if ($sortierung === null || ($sortierung['modus'] ?? '') !== 'felder') {
            // Handsortierung; ohne Handsortierung sind alle Werte 0, dann gilt Ort, Maler, Titel wie bis 1.2.6.
            return 'gk.sortierung, k.ort, k.maler, k.titel, k.id';
        }
        $teile = [];
        foreach ($sortierung['felder'] as $f) {
            $spalte = 'k.' . $f['feld'];                       // Feld stammt aus der Whitelist
            $richtung = $f['richtung'] === 'desc' ? 'DESC' : 'ASC';
            $teile[] = "({$spalte} IS NULL OR {$spalte} = '')"; // Leere immer zuletzt
            $teile[] = in_array($f['feld'], self::TEXT_FELDER, true)
                ? "kv_lower({$spalte}) {$richtung}"
                : "{$spalte} {$richtung}";
        }
        $teile[] = 'k.id';
        return implode(', ', $teile);
    }

    /**
     * Zusätzliche WHERE-Bedingungen einer Filterauswahl (mit führendem „ AND “ oder leer)
     * und benannte Parameter.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    public static function sqlWhere(?array $filter): array
    {
        if ($filter === null) {
            return ['', []];
        }
        $bedingungen = [];
        $params = [];
        foreach (self::FILTER_LISTEN as $feld) {
            if (empty($filter[$feld])) {
                continue;
            }
            $namen = [];
            foreach (array_values($filter[$feld]) as $i => $wert) {
                $namen[] = ":gr_{$feld}_{$i}";
                $params["gr_{$feld}_{$i}"] = $wert;
            }
            $bedingungen[] = "k.{$feld} IN (" . implode(', ', $namen) . ')';
        }
        foreach (self::FILTER_BEREICHE as $feld) {
            if (empty($filter[$feld])) {
                continue;
            }
            // Mit Bereich fallen Werke ohne Wert heraus.
            $teil = ["k.{$feld} IS NOT NULL", "k.{$feld} <> ''"];
            if (isset($filter[$feld]['von'])) {
                $teil[] = "k.{$feld} >= :gr_{$feld}_von";
                $params["gr_{$feld}_von"] = $filter[$feld]['von'];
            }
            if (isset($filter[$feld]['bis'])) {
                $teil[] = "k.{$feld} <= :gr_{$feld}_bis";
                $params["gr_{$feld}_bis"] = $filter[$feld]['bis'];
            }
            $bedingungen[] = '(' . implode(' AND ', $teil) . ')';
        }
        if (($filter['titel_enthaelt'] ?? '') !== '') {
            $bedingungen[] = "kv_lower(k.titel) LIKE :gr_titel ESCAPE '\\'";
            $params['gr_titel'] = '%' . addcslashes(mb_strtolower($filter['titel_enthaelt']), '%_\\') . '%';
        }
        return [$bedingungen === [] ? '' : ' AND ' . implode(' AND ', $bedingungen), $params];
    }

    /**
     * Gemeinsame Bausteine für jede Abfrage der Werke einer Gruppe. Die Abfrage muss
     * „FROM kunstwerke k JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id“ enthalten.
     * where: Mitgliedschaft, nicht gelöscht, optional die Filterauswahl des Betrachters.
     * Mit $galerieFelder (Galerie) gilt eine Sortierung nach verborgenen Feldern nicht.
     *
     * @param string[]|null $galerieFelder
     * @return array{where: string, params: array<string, mixed>, order: string, regeln: array}
     */
    public static function teile(PDO $pdo, int $gruppeId, ?array $filter = null, ?array $galerieFelder = null): array
    {
        $regeln = self::fuerGruppe($pdo, $gruppeId);
        $sortierung = $galerieFelder === null ? $regeln['sortierung'] : self::sortierungFuerGalerie($regeln['sortierung'], $galerieFelder);
        [$filterSql, $params] = self::sqlWhere($filter);
        $params['gr_gid'] = $gruppeId;
        return [
            'where'  => 'gk.gruppe_id = :gr_gid AND k.geloescht_am IS NULL' . $filterSql,
            'params' => $params,
            'order'  => self::sqlOrder($sortierung),
            'regeln' => $regeln,
        ];
    }

    // ------------------------------------------------------------ intern

    private static function alsArray(mixed $eingabe): ?array
    {
        if (is_string($eingabe)) {
            $eingabe = $eingabe === '' ? null : json_decode($eingabe, true);
        }
        return is_array($eingabe) ? $eingabe : null;
    }

    /** Ungültiges JSON: einmal protokollieren und leeren, damit es nicht bei jedem Aufruf auffällt. */
    private static function verwerfeUngueltig(int $gruppeId, string $spalte): void
    {
        try {
            $pdo = Database::get();
            $pdo->prepare("UPDATE gruppen SET {$spalte} = NULL WHERE id = :id")->execute(['id' => $gruppeId]);
            Protokoll::schreibe('gruppe_regeln_ungueltig', "Gruppe {$gruppeId}: {$spalte} war kein gültiges JSON und wurde geleert", ['id' => null, 'benutzername' => 'System']);
        } catch (\Throwable) {
            // Lesen darf daran nie scheitern.
        }
    }
}
