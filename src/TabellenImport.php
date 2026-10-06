<?php
declare(strict_types=1);

namespace App;

use PDO;
use App\Felder;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Import aus CSV oder direkt aus der Excel-Datei (erstes Tabellenblatt).
 *
 * Ablauf: Upload → normalisiere() schreibt eine saubere UTF-8-CSV (Kopfzeile
 * automatisch gefunden, Zellfarben als Status-Spalte) → Vorschau/Mapping →
 * Abgleich mit dem Bestand → Übernahme.
 */
final class TabellenImport
{
    public const PFLICHTFELDER = ['ort', 'maler', 'titel'];

    /** Spaltenname der aus Zellfarben erzeugten Status-Spalte. */
    public const FARB_SPALTE = 'Status (Zellfarbe)';

    /**
     * Import-Dropdown: key => Anzeige-Label.
     * Abgeleitet von Felder::zielfelder() – nie mehr direkt pflegen.
     * @return array<string, string>
     */
    public static function zielfelder(): array
    {
        return Felder::zielfelder();
    }

    /**
     * Importierbare kunstwerke-Felder (fuer INSERT und Abgleich).
     * @return string[]
     */
    private static function felder(): array
    {
        return Felder::felder();
    }

    /**
     * Auto-Erkennung beim Import: normalisierter Name => Feldkey.
     * @return array<string, string>
     */
    private static function autoErkennung(): array
    {
        return Felder::autoErkennung();
    }

    public const MAX_DATEIGROESSE = 20 * 1024 * 1024;

    // ---------------------------------------------------------------- Einlesen

    /**
     * Liest CSV oder XLSX und schreibt eine normalisierte CSV (UTF-8, ";").
     *
     * @return array{kopfzeile: int, farbige_zeilen: int, format: string}
     */
    public static function normalisiere(string $quelle, string $originalname, string $ziel): array
    {
        $endung = strtolower(pathinfo($originalname, PATHINFO_EXTENSION));
        $farben = [];

        if ($endung === 'xlsx') {
            [$zeilen, $farben] = self::rohzeilenXlsx($quelle);
            $format = 'Excel';
        } elseif (in_array($endung, ['csv', 'txt'], true)) {
            $zeilen = self::rohzeilenCsv($quelle);
            $format = 'CSV';
        } else {
            throw new ImportFehler(\t('import.fehler_falscher_typ'));
        }

        if ($zeilen === []) {
            throw new ImportFehler(\t('import.fehler_keine_daten'));
        }

        $kopfIndex = self::findeKopfzeile($zeilen);
        $kopf = array_map(static fn($z) => trim((string) $z), $zeilen[$kopfIndex]);
        $mitFarbe = false;
        foreach ($farben as $index => $status) {
            if ($index > $kopfIndex && $status !== null) {
                $mitFarbe = true;
                break;
            }
        }
        if ($mitFarbe) {
            $kopf[] = self::FARB_SPALTE;
        }
        $spalten = count($kopf);

        $handle = fopen($ziel, 'w');
        fputcsv($handle, $kopf, ';', '"', '');
        $farbig = 0;
        foreach ($zeilen as $index => $zeile) {
            if ($index <= $kopfIndex || self::istLeer($zeile)) {
                continue;
            }
            $zeile = array_map([self::class, 'alsText'], array_slice(array_pad($zeile, $spalten - ($mitFarbe ? 1 : 0), null), 0, $spalten - ($mitFarbe ? 1 : 0)));
            if ($mitFarbe) {
                $status = $farben[$index] ?? null;
                $zeile[] = $status !== null ? Helpers::statusLabel($status) : '';
                $farbig += $status !== null ? 1 : 0;
            }
            fputcsv($handle, $zeile, ';', '"', '');
        }
        fclose($handle);

        return ['kopfzeile' => $kopfIndex + 1, 'farbige_zeilen' => $farbig, 'format' => $format];
    }

    /** @return array{0: array<int, array>, 1: array<int, ?string>} Zeilen und Status aus Zellfarbe je Zeile */
    private static function rohzeilenXlsx(string $pfad): array
    {
        try {
            $leser = new XlsxReader();
            $blaetter = $leser->listWorksheetNames($pfad);
            // Laut Konzept zählt nur das erste Tabellenblatt ("Galerie").
            $leser->setLoadSheetsOnly([$blaetter[0]]);
            $leser->setReadEmptyCells(false);
            $blatt = $leser->load($pfad)->getSheet(0);
        } catch (\Throwable $e) {
            error_log('XLSX-Import: ' . $e->getMessage());
            throw new ImportFehler(\t('import.fehler_excel_defekt'));
        }

        $maxZeile = $blatt->getHighestDataRow();
        $maxSpalte = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($blatt->getHighestDataColumn());
        $zeilen = [];
        $farben = [];

        for ($z = 1; $z <= $maxZeile; $z++) {
            $zeile = [];
            $status = null;
            for ($s = 1; $s <= $maxSpalte; $s++) {
                if (!$blatt->cellExists([$s, $z])) {
                    $zeile[] = null;
                    continue;
                }
                $zelle = $blatt->getCell([$s, $z]);
                $zeile[] = self::zellwert($zelle);

                if ($status === null) {
                    $fuellung = $zelle->getStyle()->getFill();
                    if ($fuellung->getFillType() === Fill::FILL_SOLID) {
                        $status = Helpers::statusAusFarbe($fuellung->getStartColor()->getARGB());
                    }
                }
            }
            $zeilen[$z - 1] = $zeile;
            $farben[$z - 1] = $status;
        }

        return [$zeilen, $farben];
    }

    private static function zellwert(\PhpOffice\PhpSpreadsheet\Cell\Cell $zelle): mixed
    {
        if ($zelle->isFormula()) {
            // Der von Excel gespeicherte Wert – keine Neuberechnung (schnell, und
            // kaputte Formeln wie #REF! führen nicht zum Abbruch).
            $wert = $zelle->getOldCalculatedValue();
            return is_string($wert) && str_starts_with($wert, '#') ? null : $wert;
        }
        $wert = $zelle->getValue();
        if ($wert instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            return $wert->getPlainText();
        }
        return $wert;
    }

    /** @return array<int, array> */
    private static function rohzeilenCsv(string $pfad): array
    {
        $inhalt = (string) file_get_contents($pfad);
        $inhalt = preg_replace('/^\xEF\xBB\xBF/', '', $inhalt) ?? $inhalt;
        // Excel unter Windows speichert CSV oft in Windows-1252 statt UTF-8.
        if (!mb_check_encoding($inhalt, 'UTF-8')) {
            $inhalt = mb_convert_encoding($inhalt, 'UTF-8', 'Windows-1252');
        }

        $probe = implode("\n", array_slice(explode("\n", $inhalt, 11), 0, 10));
        $trennzeichen = ';';
        $bester = -1;
        foreach ([';', ',', "\t"] as $kandidat) {
            $anzahl = substr_count($probe, $kandidat);
            if ($anzahl > $bester) {
                $bester = $anzahl;
                $trennzeichen = $kandidat;
            }
        }

        $handle = fopen('php://temp', 'w+');
        fwrite($handle, $inhalt);
        rewind($handle);
        $zeilen = [];
        while (($zeile = fgetcsv($handle, 0, $trennzeichen, '"', '')) !== false) {
            $zeilen[] = $zeile;
        }
        fclose($handle);
        return $zeilen;
    }

    /** Erste Zeile (unter den ersten 30), die mindestens drei bekannte Spaltennamen enthält. */
    private static function findeKopfzeile(array $zeilen): int
    {
        foreach (array_slice($zeilen, 0, 30, true) as $index => $zeile) {
            $treffer = 0;
            foreach ($zeile as $zelle) {
                if (isset(self::autoErkennung()[self::normalisiereName((string) $zelle)])) {
                    $treffer++;
                }
            }
            if ($treffer >= 3) {
                return $index;
            }
        }
        return (int) array_key_first($zeilen);
    }

    private static function istLeer(array $zeile): bool
    {
        foreach ($zeile as $zelle) {
            if (trim((string) $zelle) !== '') {
                return false;
            }
        }
        return true;
    }

    private static function alsText(mixed $wert): string
    {
        if ($wert === null) {
            return '';
        }
        if (is_float($wert)) {
            return floor($wert) === $wert && abs($wert) < 1e15 ? (string) (int) $wert : (string) $wert;
        }
        if (is_bool($wert)) {
            return $wert ? '1' : '0';
        }
        return trim((string) $wert);
    }

    // ------------------------------------------------------ Vorschau & Mapping

    /** @return array{header: string[], zeilen: array[], gesamtzeilen: int} */
    public static function vorschau(string $pfad, int $limit = 20): array
    {
        $handle = fopen($pfad, 'r');
        $header = fgetcsv($handle, 0, ';', '"', '') ?: [];
        $zeilen = [];
        $gesamt = 0;
        while (($zeile = fgetcsv($handle, 0, ';', '"', '')) !== false) {
            $gesamt++;
            if (count($zeilen) < $limit) {
                $zeilen[] = $zeile;
            }
        }
        fclose($handle);
        return ['header' => array_map('strval', $header), 'zeilen' => $zeilen, 'gesamtzeilen' => $gesamt];
    }

    /** @return array<int, string> Spaltenindex => Zielfeld */
    public static function automatischesMapping(array $header): array
    {
        $mapping = [];
        $vergeben = [];
        foreach ($header as $index => $spalte) {
            $ziel = self::autoErkennung()[self::normalisiereName($spalte)] ?? '';
            // Jedes Zielfeld nur einmal vergeben. Ausnahme: die aus Zellfarben
            // erzeugte Status-Spalte verdrängt eine Text-Spalte "Status".
            if ($ziel !== '' && isset($vergeben[$ziel])) {
                if ($spalte === self::FARB_SPALTE) {
                    $mapping[$vergeben[$ziel]] = '';
                } else {
                    $ziel = '';
                }
            }
            if ($ziel !== '') {
                $vergeben[$ziel] = $index;
            }
            $mapping[$index] = $ziel;
        }
        return $mapping;
    }

    private static function normalisiereName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $name);
        return preg_replace('/[^a-z0-9]/', '', $name) ?? '';
    }

    /**
     * Bereinigt ein vom Formular geliefertes Mapping.
     *
     * @return array<int, string>
     */
    public static function pruefeMapping(mixed $mapping): array
    {
        $ergebnis = [];
        foreach (is_array($mapping) ? $mapping : [] as $index => $ziel) {
            if (is_int($index) && is_string($ziel) && array_key_exists($ziel, self::zielfelder())) {
                $ergebnis[$index] = $ziel;
            }
        }
        return $ergebnis;
    }

    public static function mappingFehler(array $mapping): ?string
    {
        $fehlend = array_diff(self::PFLICHTFELDER, $mapping);
        if ($fehlend !== []) {
            $zielfelder = self::zielfelder();
            $namen = array_map(static fn($f) => $zielfelder[$f], $fehlend);
            return \t('import.mapping_pflicht', ['spalten' => implode(', ', $namen)]);
        }
        $doppelt = array_diff_assoc(array_filter($mapping), array_unique(array_filter($mapping)));
        if ($doppelt !== []) {
            return \t('import.mapping_doppelt', ['feld' => self::zielfelder()[reset($doppelt)]]);
        }
        return null;
    }

    // ------------------------------------------------------------ Datensätze

    /**
     * @param array<int, string> $mapping
     * @return array{zeilen: array<int, array{zeilennummer: int, daten: array}>, uebersprungen: int, ungueltigeDateinamen: array}
     */
    public static function leseAlleZeilen(string $pfad, array $mapping): array
    {
        $handle = fopen($pfad, 'r');
        fgetcsv($handle, 0, ';', '"', '');

        $zeilen = [];
        $uebersprungen = 0;
        $ungueltig = [];
        $nummer = 1;
        while (($zeile = fgetcsv($handle, 0, ';', '"', '')) !== false) {
            $nummer++;
            $daten = array_fill_keys(self::felder(), null);
            $daten['bild_dateiname'] = null;
            for ($n = 2; $n <= MAX_BILDER_PRO_WERK; $n++) {
                $daten["bild_dateiname_{$n}"] = null;
            }

            foreach ($mapping as $index => $ziel) {
                if ($ziel === '' || !array_key_exists($index, $zeile)) {
                    continue;
                }
                $wert = trim((string) preg_replace('/\s+/u', ' ', (string) $zeile[$index]));
                $daten[$ziel] = $wert === '' ? null : $wert;
            }

            // Summenzeilen, Leerzeilen u. Ä.: ohne Ort, Maler und Titel kein Werk.
            if ($daten['ort'] === null && $daten['maler'] === null && $daten['titel'] === null) {
                $uebersprungen++;
                continue;
            }

            $daten['entstehungsjahr'] = Helpers::parseJahr($daten['entstehungsjahr']);
            $daten['ankaufjahr'] = Helpers::parseJahr($daten['ankaufjahr']);
            $daten['ankaufswert'] = Helpers::parseBetrag($daten['ankaufswert'], 'de'); // Excel: stets deutsche Logik
            $daten['wert'] = Helpers::parseBetrag($daten['wert'], 'de');
            $daten['werktyp'] = mb_strtolower((string) $daten['werktyp']) === 'objekt' ? 'Objekt' : 'Bild';
            $daten['status_farbe'] = Helpers::normalisiereStatus($daten['status_farbe']);
            // web_freigabe: "ja" → 1, alles andere (leer, "nein") → 0
            $daten['web_freigabe'] = mb_strtolower(trim((string) ($daten['web_freigabe'] ?? ''))) === 'ja' ? 1 : 0;

            if ($daten['bild_dateiname'] !== null) {
                $gueltig = Bilder::gueltigerDateiname($daten['bild_dateiname']);
                if ($gueltig === null) {
                    $ungueltig[] = ['zeilennummer' => $nummer, 'daten' => $daten];
                }
                $daten['bild_dateiname'] = $gueltig;
            }
            for ($n = 2; $n <= MAX_BILDER_PRO_WERK; $n++) {
                $feld = "bild_dateiname_{$n}";
                if ($daten[$feld] !== null) {
                    $gueltig = Bilder::gueltigerDateiname($daten[$feld]);
                    if ($gueltig === null) {
                        $ungueltig[] = ['zeilennummer' => $nummer, 'daten' => $daten];
                    }
                    $daten[$feld] = $gueltig;
                }
            }

            $zeilen[] = ['zeilennummer' => $nummer, 'daten' => $daten];
        }
        fclose($handle);

        return ['zeilen' => $zeilen, 'uebersprungen' => $uebersprungen, 'ungueltigeDateinamen' => $ungueltig];
    }

    /** Ersetzt abweichende Ort-Schreibweisen durch den zusammengeführten Namen. */
    public static function wendeOrtAliaseAn(PDO $pdo, array &$zeilen): int
    {
        $aliase = $pdo->query('SELECT alias, ziel FROM ort_alias')->fetchAll(PDO::FETCH_KEY_PAIR);
        $ersetzt = 0;
        foreach ($zeilen as &$eintrag) {
            $schluessel = Helpers::ortSchluessel($eintrag['daten']['ort']);
            if (isset($aliase[$schluessel]) && $aliase[$schluessel] !== $eintrag['daten']['ort']) {
                $eintrag['daten']['ort'] = $aliase[$schluessel];
                $ersetzt++;
            }
        }
        return $ersetzt;
    }

    // --------------------------------------------------------------- Abgleich

    public static function abgleichsschluessel(array $daten): string
    {
        return Helpers::ortSchluessel($daten['ort'] ?? '') . '|'
            . Helpers::ortSchluessel($daten['maler'] ?? '') . '|'
            . Helpers::ortSchluessel($daten['titel'] ?? '');
    }

    /**
     * Klassifiziert jede Zeile als neu / geändert / unverändert.
     * Nur zugeordnete Felder werden verglichen und später geschrieben –
     * fehlt eine Spalte in der Datei, bleiben die Werte im Bestand erhalten.
     *
     * Gleiche Ort/Maler/Titel-Kombinationen (z. B. mehrere „o.T.“) werden der
     * Reihe nach zugeordnet: 1. Vorkommen in der Datei ↔ ältester Datensatz usw.
     * Wurden Ort/Maler/Titel im Programm geändert, findet der alte Schlüssel
     * (werk_schluessel_alias) das Werk trotzdem.
     *
     * Geänderte Werke, die im Programm bearbeitet wurden, landen in
     * "geschuetzt" statt "geaendert" – sie werden nur auf Wunsch überschrieben.
     *
     * @param string[] $gemappt
     */
    public static function berechneAbgleich(PDO $pdo, array $zeilen, array $gemappt): array
    {
        $bestand = [];
        $nachId = [];
        $extraBilderSql = '';
        for ($n = 2; $n <= MAX_BILDER_PRO_WERK; $n++) {
            $offset = $n - 2;
            $extraBilderSql .= ", (SELECT b.dateiname FROM bilder b WHERE b.kunstwerk_id = k.id AND b.ist_hauptbild = 0 ORDER BY b.sortierung, b.id LIMIT 1 OFFSET {$offset}) AS bild_dateiname_{$n}";
        }
        $sql = "SELECT k.*, (SELECT b.dateiname FROM bilder b WHERE b.kunstwerk_id = k.id AND b.ist_hauptbild = 1 ORDER BY b.sortierung, b.id LIMIT 1) AS bild_dateiname{$extraBilderSql}
                FROM kunstwerke k ORDER BY k.id";
        foreach ($pdo->query($sql) as $row) {
            $bestand[self::abgleichsschluessel($row)][] = $row;
            $nachId[(int) $row['id']] = $row;
        }
        $aliase = [];
        foreach ($pdo->query('SELECT schluessel, kunstwerk_id FROM werk_schluessel_alias ORDER BY kunstwerk_id') as $a) {
            $aliase[$a['schluessel']][] = (int) $a['kunstwerk_id'];
        }

        $extraBildfelder = [];
        for ($n = 2; $n <= MAX_BILDER_PRO_WERK; $n++) {
            $extraBildfelder[] = "bild_dateiname_{$n}";
        }
        $vergleich = array_values(array_intersect(array_merge(self::felder(), ['bild_dateiname'], $extraBildfelder), $gemappt));

        // Tombstones: endgültig gelöschte Schlüssel – absorbieren neue Zeilen.
        $grabsteine = [];
        foreach ($pdo->query("SELECT schluessel, COUNT(*) AS anzahl FROM werk_tombstone GROUP BY schluessel") as $g) {
            $grabsteine[$g['schluessel']] = (int) $g['anzahl'];
        }
        $grabsteineVerwendet = [];

        $ergebnis = ['neu' => [], 'geaendert' => [], 'geschuetzt' => [], 'unveraendert' => 0, 'fehlerBild' => [], 'duplikate' => [], 'imPapierkorb' => []];
        $vorkommen = [];
        $verwendet = [];

        foreach ($zeilen as $eintrag) {
            $daten = $eintrag['daten'];
            $schluessel = self::abgleichsschluessel($daten);
            $vorkommen[$schluessel] = ($vorkommen[$schluessel] ?? 0) + 1;
            if ($vorkommen[$schluessel] > 1) {
                $ergebnis['duplikate'][] = $eintrag;
            }

            $bildFehler = $daten['bild_dateiname'] !== null && !is_file(Bilder::originalPfad($daten['bild_dateiname']));
            if (!$bildFehler) {
                for ($n = 2; $n <= MAX_BILDER_PRO_WERK; $n++) {
                    $fn = $daten["bild_dateiname_{$n}"] ?? null;
                    if ($fn !== null && !is_file(Bilder::originalPfad($fn))) {
                        $bildFehler = true;
                        break;
                    }
                }
            }
            if ($bildFehler) {
                $ergebnis['fehlerBild'][] = $eintrag;
            }

            // Erster noch nicht zugeordneter Datensatz mit diesem Schlüssel,
            // sonst einer, der früher diesen Schlüssel hatte.
            $vorhanden = null;
            foreach ($bestand[$schluessel] ?? [] as $row) {
                if (!isset($verwendet[(int) $row['id']])) {
                    $vorhanden = $row;
                    break;
                }
            }
            if ($vorhanden === null) {
                foreach ($aliase[$schluessel] ?? [] as $id) {
                    if (!isset($verwendet[$id]) && isset($nachId[$id])) {
                        $vorhanden = $nachId[$id];
                        break;
                    }
                }
            }
            if ($vorhanden === null) {
                // Tombstone vorhanden? Dann diese Zeile überspringen (nicht neu anlegen).
                $grabVorhanden = ($grabsteine[$schluessel] ?? 0) - ($grabsteineVerwendet[$schluessel] ?? 0);
                if ($grabVorhanden > 0) {
                    $grabsteineVerwendet[$schluessel] = ($grabsteineVerwendet[$schluessel] ?? 0) + 1;
                    continue;
                }
                $ergebnis['neu'][] = $eintrag;
                continue;
            }
            $verwendet[(int) $vorhanden['id']] = true;

            // Im Papierkorb – nicht importieren, aber Zeile als Hinweis merken.
            if ($vorhanden['geloescht_am'] !== null) {
                $eintrag['kunstwerk_id'] = (int) $vorhanden['id'];
                $ergebnis['imPapierkorb'][] = $eintrag;
                continue;
            }

            $unterschiede = [];
            foreach ($vergleich as $feld) {
                if (!self::gleich($feld, $daten[$feld], $vorhanden[$feld])) {
                    $unterschiede[] = $feld;
                }
            }

            if ($unterschiede === []) {
                $ergebnis['unveraendert']++;
                continue;
            }
            $eintrag['kunstwerk_id'] = (int) $vorhanden['id'];
            $eintrag['unterschiede'] = $unterschiede;
            if ($vorhanden['bearbeitet_am'] !== null) {
                $eintrag['bearbeitet_am'] = $vorhanden['bearbeitet_am'];
                $eintrag['bestand'] = $vorhanden;
                $ergebnis['geschuetzt'][] = $eintrag;
            } else {
                $ergebnis['geaendert'][] = $eintrag;
            }
        }

        return $ergebnis;
    }

    private static function gleich(string $feld, mixed $neu, mixed $alt): bool
    {
        if (in_array($feld, ['ankaufswert', 'wert'], true)) {
            return ($neu === null && $alt === null) || ($neu !== null && $alt !== null && abs((float) $neu - (float) $alt) < 0.005);
        }
        if (in_array($feld, ['entstehungsjahr', 'ankaufjahr'], true)) {
            return ($neu === null ? null : (int) $neu) === ($alt === null ? null : (int) $alt);
        }
        return (string) ($neu ?? '') === (string) ($alt ?? '');
    }

    /**
     * @param string[] $gemappt
     * @param bool $bearbeiteteUeberschreiben auch im Programm bearbeitete Werke mit Tabellenwerten überschreiben
     */
    public static function uebernehmen(PDO $pdo, array $abgleich, array $gemappt, bool $bearbeiteteUeberschreiben = false): void
    {
        $felder = array_values(array_intersect(self::felder(), $gemappt));
        $bildGemappt = in_array('bild_dateiname', $gemappt, true);

        $pdo->beginTransaction();

        $insertFelder = self::felder();
        $insert = $pdo->prepare(
            'INSERT INTO kunstwerke (' . implode(', ', $insertFelder) . ') VALUES (:' . implode(', :', $insertFelder) . ')'
        );
        $update = $felder === [] ? null : $pdo->prepare(
            'UPDATE kunstwerke SET ' . implode(', ', array_map(static fn($f) => "{$f} = :{$f}", $felder)) . ' WHERE id = :id'
        );
        $bildLoeschen = $pdo->prepare('DELETE FROM bilder WHERE kunstwerk_id = :id AND ist_hauptbild = 1');
        // Die Verknüpfung wird auch gespeichert, wenn die Datei noch fehlt: wird
        // das Bild später hochgeladen, erscheint es automatisch beim Werk.
        $bildEinfuegen = $pdo->prepare('INSERT INTO bilder (kunstwerk_id, dateiname, ist_hauptbild, sortierung) VALUES (:id, :d, 1, 0)');
        $bildEinfuegenExtra = $pdo->prepare('INSERT INTO bilder (kunstwerk_id, dateiname, ist_hauptbild, sortierung) VALUES (:id, :d, 0, :sort)');
        $extraBilderLoeschen = $pdo->prepare('DELETE FROM bilder WHERE kunstwerk_id = :id AND ist_hauptbild = 0');
        $extraBilderGemappt = false;
        for ($n = 2; $n <= MAX_BILDER_PRO_WERK; $n++) {
            if (in_array("bild_dateiname_{$n}", $gemappt, true)) {
                $extraBilderGemappt = true;
                break;
            }
        }

        foreach ($abgleich['neu'] as $eintrag) {
            $daten = $eintrag['daten'];
            $insert->execute(array_intersect_key($daten, array_flip($insertFelder)));
            $neuId = (int) $pdo->lastInsertId();
            if ($daten['bild_dateiname'] !== null) {
                $bildEinfuegen->execute(['id' => $neuId, 'd' => $daten['bild_dateiname']]);
            }
            for ($n = 2; $n <= MAX_BILDER_PRO_WERK; $n++) {
                $fn = $daten["bild_dateiname_{$n}"] ?? null;
                if ($fn !== null) {
                    $bildEinfuegenExtra->execute(['id' => $neuId, 'd' => $fn, 'sort' => $n - 1]);
                }
            }
        }

        // Nach dem Überschreiben entspricht das Werk wieder der Tabelle.
        $synchron = $pdo->prepare('UPDATE kunstwerke SET bearbeitet_am = NULL WHERE id = :id');
        $zuAktualisieren = $bearbeiteteUeberschreiben
            ? array_merge($abgleich['geaendert'], $abgleich['geschuetzt'])
            : $abgleich['geaendert'];

        foreach ($zuAktualisieren as $eintrag) {
            $synchron->execute(['id' => $eintrag['kunstwerk_id']]);
            $daten = $eintrag['daten'];
            $id = $eintrag['kunstwerk_id'];
            if ($update !== null) {
                $update->execute(array_merge(array_intersect_key($daten, array_flip($felder)), ['id' => $id]));
            }
            if ($bildGemappt && in_array('bild_dateiname', $eintrag['unterschiede'], true)) {
                $bildLoeschen->execute(['id' => $id]);
                if ($daten['bild_dateiname'] !== null) {
                    $bildEinfuegen->execute(['id' => $id, 'd' => $daten['bild_dateiname']]);
                }
            }
            if ($extraBilderGemappt) {
                $extraGeaendert = false;
                for ($n = 2; $n <= MAX_BILDER_PRO_WERK; $n++) {
                    if (in_array("bild_dateiname_{$n}", $eintrag['unterschiede'], true)) {
                        $extraGeaendert = true;
                        break;
                    }
                }
                if ($extraGeaendert) {
                    $extraBilderLoeschen->execute(['id' => $id]);
                    for ($n = 2; $n <= MAX_BILDER_PRO_WERK; $n++) {
                        $fn = $daten["bild_dateiname_{$n}"] ?? null;
                        if ($fn !== null) {
                            $bildEinfuegenExtra->execute(['id' => $id, 'd' => $fn, 'sort' => $n - 1]);
                        }
                    }
                }
            }
        }

        $pdo->commit();
    }

    /** Entfernt abgebrochene Import-Zwischendateien, die älter als ein Tag sind. */
    public static function raeumeAuf(): void
    {
        foreach (glob(IMPORT_TMP_PATH . '/import_*.csv') ?: [] as $datei) {
            if (filemtime($datei) < time() - 86400) {
                @unlink($datei);
            }
        }
    }
}
