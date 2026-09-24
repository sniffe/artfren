<?php
declare(strict_types=1);

namespace App;

use PDO;
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
    public const ZIELFELDER = [
        '' => '– ignorieren –',
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
        'werktyp' => 'Bild/Objekt',
        'status_farbe' => 'Status (Farbe)',
        'bild_dateiname' => 'Bild-Dateiname',
    ];

    public const PFLICHTFELDER = ['ort', 'maler', 'titel'];

    /** Spaltenname der aus Zellfarben erzeugten Status-Spalte. */
    public const FARB_SPALTE = 'Status (Zellfarbe)';

    private const AUTO_ERKENNUNG = [
        'ort' => 'ort',
        'standort' => 'ort',
        'maler' => 'maler',
        'kuenstler' => 'maler',
        'kuenstlerin' => 'maler',
        'titel' => 'titel',
        'format' => 'format',
        'technik' => 'technik',
        'entstehungsjahr' => 'entstehungsjahr',
        'enstehungsjahr' => 'entstehungsjahr',
        'jahr' => 'entstehungsjahr',
        'ankaufjahr' => 'ankaufjahr',
        'ankaufsjahr' => 'ankaufjahr',
        'ankauf' => 'ankauf',
        'ankaufswert' => 'ankaufswert',
        'ankaufwert' => 'ankaufswert',
        'wert' => 'wert',
        'bild' => 'werktyp',
        'werktyp' => 'werktyp',
        'typ' => 'werktyp',
        'status' => 'status_farbe',
        'statusfarbe' => 'status_farbe',
        'statuszellfarbe' => 'status_farbe',
        'dateiname' => 'bild_dateiname',
        'bilddateiname' => 'bild_dateiname',
    ];

    private const FELDER = [
        'ort', 'maler', 'titel', 'format', 'technik', 'entstehungsjahr', 'ankaufjahr',
        'ankauf', 'ankaufswert', 'wert', 'werktyp', 'status_farbe',
    ];

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
            throw new ImportFehler('Bitte eine .xlsx- oder .csv-Datei hochladen.');
        }

        if ($zeilen === []) {
            throw new ImportFehler('Die Datei enthält keine Daten.');
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
            throw new ImportFehler('Die Excel-Datei konnte nicht gelesen werden. Ist sie beschädigt oder passwortgeschützt?');
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
                if (isset(self::AUTO_ERKENNUNG[self::normalisiereName((string) $zelle)])) {
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
            $ziel = self::AUTO_ERKENNUNG[self::normalisiereName($spalte)] ?? '';
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
            if (is_int($index) && is_string($ziel) && array_key_exists($ziel, self::ZIELFELDER)) {
                $ergebnis[$index] = $ziel;
            }
        }
        return $ergebnis;
    }

    public static function mappingFehler(array $mapping): ?string
    {
        $fehlend = array_diff(self::PFLICHTFELDER, $mapping);
        if ($fehlend !== []) {
            $namen = array_map(static fn($f) => self::ZIELFELDER[$f], $fehlend);
            return 'Bitte eine Spalte zuordnen für: ' . implode(', ', $namen) . ' (daran werden Werke beim erneuten Import wiedererkannt).';
        }
        $doppelt = array_diff_assoc(array_filter($mapping), array_unique(array_filter($mapping)));
        if ($doppelt !== []) {
            return 'Das Feld „' . self::ZIELFELDER[reset($doppelt)] . '“ ist mehreren Spalten zugeordnet.';
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
            $daten = array_fill_keys(self::FELDER, null);
            $daten['bild_dateiname'] = null;

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
            $daten['ankaufswert'] = Helpers::parseBetrag($daten['ankaufswert']);
            $daten['wert'] = Helpers::parseBetrag($daten['wert']);
            $daten['werktyp'] = mb_strtolower((string) $daten['werktyp']) === 'objekt' ? 'Objekt' : 'Bild';
            $daten['status_farbe'] = Helpers::normalisiereStatus($daten['status_farbe']);

            if ($daten['bild_dateiname'] !== null) {
                $gueltig = Bilder::gueltigerDateiname($daten['bild_dateiname']);
                if ($gueltig === null) {
                    $ungueltig[] = ['zeilennummer' => $nummer, 'daten' => $daten];
                }
                $daten['bild_dateiname'] = $gueltig;
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
        $sql = "SELECT k.*, (SELECT b.dateiname FROM bilder b WHERE b.kunstwerk_id = k.id AND b.ist_hauptbild = 1 ORDER BY b.sortierung, b.id LIMIT 1) AS bild_dateiname
                FROM kunstwerke k ORDER BY k.id";
        foreach ($pdo->query($sql) as $row) {
            $bestand[self::abgleichsschluessel($row)][] = $row;
            $nachId[(int) $row['id']] = $row;
        }
        $aliase = [];
        foreach ($pdo->query('SELECT schluessel, kunstwerk_id FROM werk_schluessel_alias ORDER BY kunstwerk_id') as $a) {
            $aliase[$a['schluessel']][] = (int) $a['kunstwerk_id'];
        }

        $vergleich = array_values(array_intersect(array_merge(self::FELDER, ['bild_dateiname']), $gemappt));

        $ergebnis = ['neu' => [], 'geaendert' => [], 'geschuetzt' => [], 'unveraendert' => 0, 'fehlerBild' => [], 'duplikate' => []];
        $vorkommen = [];
        $verwendet = [];

        foreach ($zeilen as $eintrag) {
            $daten = $eintrag['daten'];
            $schluessel = self::abgleichsschluessel($daten);
            $vorkommen[$schluessel] = ($vorkommen[$schluessel] ?? 0) + 1;
            if ($vorkommen[$schluessel] > 1) {
                $ergebnis['duplikate'][] = $eintrag;
            }

            if ($daten['bild_dateiname'] !== null && !is_file(Bilder::originalPfad($daten['bild_dateiname']))) {
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
                $ergebnis['neu'][] = $eintrag;
                continue;
            }
            $verwendet[(int) $vorhanden['id']] = true;

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
        $felder = array_values(array_intersect(self::FELDER, $gemappt));
        $bildGemappt = in_array('bild_dateiname', $gemappt, true);

        $pdo->beginTransaction();

        $insert = $pdo->prepare(
            'INSERT INTO kunstwerke (' . implode(', ', self::FELDER) . ') VALUES (:' . implode(', :', self::FELDER) . ')'
        );
        $update = $felder === [] ? null : $pdo->prepare(
            'UPDATE kunstwerke SET ' . implode(', ', array_map(static fn($f) => "{$f} = :{$f}", $felder)) . ' WHERE id = :id'
        );
        $bildLoeschen = $pdo->prepare('DELETE FROM bilder WHERE kunstwerk_id = :id AND ist_hauptbild = 1');
        // Die Verknüpfung wird auch gespeichert, wenn die Datei noch fehlt: wird
        // das Bild später hochgeladen, erscheint es automatisch beim Werk.
        $bildEinfuegen = $pdo->prepare('INSERT INTO bilder (kunstwerk_id, dateiname, ist_hauptbild, sortierung) VALUES (:id, :d, 1, 0)');

        foreach ($abgleich['neu'] as $eintrag) {
            $daten = $eintrag['daten'];
            $insert->execute(array_intersect_key($daten, array_flip(self::FELDER)));
            if ($daten['bild_dateiname'] !== null) {
                $bildEinfuegen->execute(['id' => (int) $pdo->lastInsertId(), 'd' => $daten['bild_dateiname']]);
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
