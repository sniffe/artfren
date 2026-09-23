<?php
declare(strict_types=1);

namespace App;

/**
 * Hilfsfunktionen für den CSV-Import (Parsing, Mapping-Erkennung, Abgleich).
 */
final class CsvImport
{
    /** Bekannte DB-Zielfelder für das Spalten-Mapping. */
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
        'status_farbe' => 'Status-Farbe',
        'bild_dateiname' => 'Bild-Dateiname',
    ];

    private const AUTO_ERKENNUNG = [
        'ort' => 'ort',
        'maler' => 'maler',
        'titel' => 'titel',
        'format' => 'format',
        'technik' => 'technik',
        'entstehungsjahr' => 'entstehungsjahr',
        'enstehungsjahr' => 'entstehungsjahr',
        'ankaufjahr' => 'ankaufjahr',
        'ankauf' => 'ankauf',
        'ankaufswert' => 'ankaufswert',
        'wert' => 'wert',
        'bild' => 'werktyp',
        'werktyp' => 'werktyp',
        'status' => 'status_farbe',
        'statusfarbe' => 'status_farbe',
        'dateiname' => 'bild_dateiname',
        'bilddateiname' => 'bild_dateiname',
    ];

    public static function erkenneTrennzeichen(string $ersteZeile): string
    {
        $semikolons = substr_count($ersteZeile, ';');
        $kommas = substr_count($ersteZeile, ',');
        return $semikolons > $kommas ? ';' : ',';
    }

    /**
     * @return array{header: string[], zeilen: array[], trennzeichen: string, gesamtzeilen: int}
     */
    public static function vorschau(string $pfad, int $limit = 20): array
    {
        $handle = fopen($pfad, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Datei konnte nicht gelesen werden.');
        }

        $ersteZeile = fgets($handle);
        rewind($handle);
        $trennzeichen = self::erkenneTrennzeichen((string) $ersteZeile);

        $header = fgetcsv($handle, 0, $trennzeichen);
        if ($header === false) {
            fclose($handle);
            throw new \RuntimeException('Die Datei enthält keine gültige Kopfzeile.');
        }
        $header = array_map(static fn($h) => trim((string) $h, " \t\n\r\0\x0B\xEF\xBB\xBF"), $header);

        $zeilen = [];
        $gesamt = 0;
        while (($zeile = fgetcsv($handle, 0, $trennzeichen)) !== false) {
            if ($zeile === [null] || $zeile === false) {
                continue;
            }
            if (count(array_filter($zeile, fn($z) => trim((string) $z) !== '')) === 0) {
                continue;
            }
            $gesamt++;
            if (count($zeilen) < $limit) {
                $zeilen[] = $zeile;
            }
        }
        fclose($handle);

        return ['header' => $header, 'zeilen' => $zeilen, 'trennzeichen' => $trennzeichen, 'gesamtzeilen' => $gesamt];
    }

    /**
     * @param string[] $header
     * @return array<int, string> Spaltenindex => Zielfeld
     */
    public static function automatischesMapping(array $header): array
    {
        $mapping = [];
        foreach ($header as $index => $spalte) {
            $normalisiert = self::normalisiereName($spalte);
            $mapping[$index] = self::AUTO_ERKENNUNG[$normalisiert] ?? '';
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
     * Liest die komplette Datei anhand des Mappings ein und liefert
     * normalisierte Datensätze (DB-Feld => Wert) je Zeile.
     *
     * @param array<int, string> $mapping Spaltenindex => Zielfeld
     * @return array<int, array{zeilennummer: int, daten: array}>
     */
    public static function leseAlleZeilen(string $pfad, array $mapping, string $trennzeichen): array
    {
        $handle = fopen($pfad, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Datei konnte nicht gelesen werden.');
        }

        fgetcsv($handle, 0, $trennzeichen); // Kopfzeile überspringen

        $ergebnis = [];
        $zeilennummer = 1;
        while (($zeile = fgetcsv($handle, 0, $trennzeichen)) !== false) {
            $zeilennummer++;
            if (count(array_filter($zeile, fn($z) => trim((string) $z) !== '')) === 0) {
                continue;
            }

            $daten = [
                'ort' => null, 'maler' => null, 'titel' => null, 'format' => null,
                'technik' => null, 'entstehungsjahr' => null, 'ankaufjahr' => null,
                'ankauf' => null, 'ankaufswert' => null, 'wert' => null,
                'werktyp' => 'Bild', 'status_farbe' => null, 'bild_dateiname' => null,
            ];

            foreach ($mapping as $index => $ziel) {
                if ($ziel === '' || !array_key_exists($index, $zeile)) {
                    continue;
                }
                $wert = trim((string) $zeile[$index]);
                $daten[$ziel] = $wert === '' ? null : $wert;
            }

            if ($daten['entstehungsjahr'] !== null) {
                $daten['entstehungsjahr'] = self::zuInt($daten['entstehungsjahr']);
            }
            if ($daten['ankaufjahr'] !== null) {
                $daten['ankaufjahr'] = self::zuInt($daten['ankaufjahr']);
            }
            if ($daten['ankaufswert'] !== null) {
                $daten['ankaufswert'] = self::zuFloat($daten['ankaufswert']);
            }
            if ($daten['wert'] !== null) {
                $daten['wert'] = self::zuFloat($daten['wert']);
            }
            if ($daten['werktyp'] === null || !in_array($daten['werktyp'], ['Bild', 'Objekt'], true)) {
                $daten['werktyp'] = 'Bild';
            }

            $ergebnis[] = ['zeilennummer' => $zeilennummer, 'daten' => $daten];
        }
        fclose($handle);

        return $ergebnis;
    }

    private static function zuInt(?string $wert): ?int
    {
        if ($wert === null) {
            return null;
        }
        $bereinigt = preg_replace('/[^0-9-]/', '', $wert);
        return $bereinigt !== '' && $bereinigt !== null ? (int) $bereinigt : null;
    }

    private static function zuFloat(?string $wert): ?float
    {
        if ($wert === null) {
            return null;
        }
        $bereinigt = str_replace(['.', ' ', '€'], '', $wert);
        $bereinigt = str_replace(',', '.', $bereinigt);
        $bereinigt = preg_replace('/[^0-9.\-]/', '', $bereinigt);
        return $bereinigt !== '' && $bereinigt !== null && is_numeric($bereinigt) ? (float) $bereinigt : null;
    }

    public static function abgleichsschluessel(array $daten): string
    {
        $teil = static fn(?string $s) => mb_strtolower(trim((string) $s));
        return $teil($daten['ort'] ?? '') . '|' . $teil($daten['maler'] ?? '') . '|' . $teil($daten['titel'] ?? '');
    }

    /**
     * Vergleicht die eingelesenen CSV-Zeilen mit dem Datenbankbestand und
     * klassifiziert jede Zeile als neu / geändert / unverändert. Prüft außerdem,
     * ob referenzierte Bilddateien im Bilder-Ordner vorhanden sind.
     *
     * @param array $zeilen Rückgabe von self::leseAlleZeilen()
     * @return array{neu: array, geaendert: array, unveraendert: int, fehlerBild: array, duplikate: array}
     */
    public static function berechneAbgleich(\PDO $pdo, array $zeilen): array
    {
        $bestand = [];
        foreach ($pdo->query('SELECT * FROM kunstwerke') as $row) {
            $schluessel = self::abgleichsschluessel($row);
            $bestand[$schluessel] = $row;
        }

        $vergleichsfelder = ['ort', 'maler', 'titel', 'format', 'technik', 'entstehungsjahr', 'ankaufjahr', 'ankauf', 'ankaufswert', 'wert', 'werktyp', 'status_farbe'];

        $neu = [];
        $geaendert = [];
        $unveraendertAnzahl = 0;
        $fehlerBild = [];
        $duplikate = [];
        $gesehen = [];

        foreach ($zeilen as $eintrag) {
            $daten = $eintrag['daten'];
            $schluessel = self::abgleichsschluessel($daten);

            if (isset($gesehen[$schluessel])) {
                $duplikate[] = $eintrag;
            }
            $gesehen[$schluessel] = true;

            if ($daten['bild_dateiname'] !== null && !is_file(BILDER_PATH . '/' . $daten['bild_dateiname'])) {
                $fehlerBild[] = $eintrag;
            }

            if (!isset($bestand[$schluessel])) {
                $neu[] = $eintrag;
                continue;
            }

            $vorhanden = $bestand[$schluessel];
            $geaendertErkannt = false;
            foreach ($vergleichsfelder as $feld) {
                if (!array_key_exists($feld, $daten)) {
                    continue;
                }
                $neuerWert = $daten[$feld];
                $alterWert = $vorhanden[$feld];
                if ($feld === 'ankaufswert' || $feld === 'wert') {
                    $geaendertErkannt = $geaendertErkannt || (float) $neuerWert !== (float) $alterWert;
                } elseif ($feld === 'entstehungsjahr' || $feld === 'ankaufjahr') {
                    $geaendertErkannt = $geaendertErkannt || (int) $neuerWert !== (int) $alterWert;
                } else {
                    $geaendertErkannt = $geaendertErkannt || (string) $neuerWert !== (string) ($alterWert ?? '');
                }
            }

            if ($geaendertErkannt) {
                $eintrag['kunstwerk_id'] = (int) $vorhanden['id'];
                $geaendert[] = $eintrag;
            } else {
                $unveraendertAnzahl++;
            }
        }

        return [
            'neu' => $neu,
            'geaendert' => $geaendert,
            'unveraendert' => $unveraendertAnzahl,
            'fehlerBild' => $fehlerBild,
            'duplikate' => $duplikate,
        ];
    }

    public static function uebernehmen(\PDO $pdo, array $abgleich): void
    {
        $pdo->beginTransaction();

        $insertStmt = $pdo->prepare(
            'INSERT INTO kunstwerke (ort, maler, titel, format, technik, entstehungsjahr, ankaufjahr, ankauf, ankaufswert, wert, werktyp, status_farbe)
             VALUES (:ort, :maler, :titel, :format, :technik, :entstehungsjahr, :ankaufjahr, :ankauf, :ankaufswert, :wert, :werktyp, :status_farbe)'
        );
        $updateStmt = $pdo->prepare(
            'UPDATE kunstwerke SET ort = :ort, maler = :maler, titel = :titel, format = :format, technik = :technik,
                entstehungsjahr = :entstehungsjahr, ankaufjahr = :ankaufjahr, ankauf = :ankauf,
                ankaufswert = :ankaufswert, wert = :wert, werktyp = :werktyp, status_farbe = :status_farbe
             WHERE id = :id'
        );
        $loeschBilder = $pdo->prepare('DELETE FROM bilder WHERE kunstwerk_id = :id');
        $insertBild = $pdo->prepare('INSERT INTO bilder (kunstwerk_id, dateiname, ist_hauptbild, sortierung) VALUES (:id, :dateiname, 1, 0)');

        foreach ($abgleich['neu'] as $eintrag) {
            $daten = $eintrag['daten'];
            $insertStmt->execute([
                'ort' => $daten['ort'], 'maler' => $daten['maler'], 'titel' => $daten['titel'],
                'format' => $daten['format'], 'technik' => $daten['technik'],
                'entstehungsjahr' => $daten['entstehungsjahr'], 'ankaufjahr' => $daten['ankaufjahr'],
                'ankauf' => $daten['ankauf'], 'ankaufswert' => $daten['ankaufswert'], 'wert' => $daten['wert'],
                'werktyp' => $daten['werktyp'], 'status_farbe' => $daten['status_farbe'],
            ]);
            $kunstwerkId = (int) $pdo->lastInsertId();
            if ($daten['bild_dateiname'] !== null && is_file(BILDER_PATH . '/' . $daten['bild_dateiname'])) {
                $insertBild->execute(['id' => $kunstwerkId, 'dateiname' => $daten['bild_dateiname']]);
            }
        }

        foreach ($abgleich['geaendert'] as $eintrag) {
            $daten = $eintrag['daten'];
            $kunstwerkId = $eintrag['kunstwerk_id'];
            $updateStmt->execute([
                'ort' => $daten['ort'], 'maler' => $daten['maler'], 'titel' => $daten['titel'],
                'format' => $daten['format'], 'technik' => $daten['technik'],
                'entstehungsjahr' => $daten['entstehungsjahr'], 'ankaufjahr' => $daten['ankaufjahr'],
                'ankauf' => $daten['ankauf'], 'ankaufswert' => $daten['ankaufswert'], 'wert' => $daten['wert'],
                'werktyp' => $daten['werktyp'], 'status_farbe' => $daten['status_farbe'],
                'id' => $kunstwerkId,
            ]);
            if ($daten['bild_dateiname'] !== null && is_file(BILDER_PATH . '/' . $daten['bild_dateiname'])) {
                $loeschBilder->execute(['id' => $kunstwerkId]);
                $insertBild->execute(['id' => $kunstwerkId, 'dateiname' => $daten['bild_dateiname']]);
            }
        }

        $pdo->commit();
    }
}
