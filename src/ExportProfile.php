<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Verwaltung und Auflösung von Export-Profilen.
 * Profile steuern Feldauswahl, Schalter und Sprache für den PDF-Export
 * (und optionalen Profil-basierten Excel-Export in Paket 6a).
 */
final class ExportProfile
{
    // ---------------------------------------------------------------- Lesen

    /** Alle Profile, neueste zuerst. */
    public static function alle(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT ep.*, b.benutzername AS erstellt_von_name
             FROM export_profile ep
             LEFT JOIN benutzer b ON b.id = ep.erstellt_von
             ORDER BY ep.name COLLATE NOCASE'
        )->fetchAll();
    }

    /** Ein Profil anhand der ID laden (oder null). */
    public static function finde(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT ep.*, b.benutzername AS erstellt_von_name
             FROM export_profile ep
             LEFT JOIN benutzer b ON b.id = ep.erstellt_von
             WHERE ep.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $zeile = $stmt->fetch();
        return $zeile === false ? null : $zeile;
    }

    /** Standard-Profil (ist_standard = 1) oder null. */
    public static function standard(PDO $pdo): ?array
    {
        $zeile = $pdo->query("SELECT * FROM export_profile WHERE ist_standard = 1 LIMIT 1")->fetch();
        return $zeile === false ? null : $zeile;
    }

    // --------------------------------------------------- Feldauflösung

    /**
     * Standard-Felder (in_pdf = true) als Schlüssel-Array.
     * @return string[]
     */
    public static function standardFelder(): array
    {
        return array_column(
            array_filter(Felder::alle(), static fn(array $f): bool => $f['in_pdf']),
            'key'
        );
    }

    /**
     * Felder aus einem Profil-Array (comma-separated) auflösen und gegen
     * die Registry prüfen. Unbekannte Schlüssel werden ignoriert.
     * @return string[]
     */
    public static function felderAusProfil(array $profil): array
    {
        $bekannte = array_column(Felder::alle(), 'key', 'key');
        $keys = array_filter(
            array_map('trim', explode(',', (string) ($profil['felder'] ?? ''))),
            static fn(string $k): bool => $k !== '' && isset($bekannte[$k])
        );
        return array_values($keys);
    }

    /**
     * Felder aus GET-Parameter felder[] auflösen und gegen Registry prüfen.
     * Reihenfolge des Registers wird eingehalten.
     * @param string[] $getFelder
     * @return string[]
     */
    public static function felderAusGet(array $getFelder): array
    {
        $getSet = array_flip(array_filter(array_map('trim', $getFelder)));
        $ergebnis = [];
        foreach (Felder::alle() as $f) {
            if (isset($getSet[$f['key']])) {
                $ergebnis[] = $f['key'];
            }
        }
        return $ergebnis;
    }

    // ----------------------------------------------------- Schreiben

    /**
     * Profil speichern (anlegen oder aktualisieren).
     * Gibt die ID des gespeicherten Profils zurück.
     */
    public static function speichern(PDO $pdo, array $daten, ?int $id = null): int
    {
        $felder = implode(',', self::felderAusGet((array) ($daten['felder'] ?? [])));
        $name    = mb_substr(trim((string) ($daten['name'] ?? '')), 0, 80);
        $bild    = (int) !empty($daten['bild']);
        $titel   = (int) !empty($daten['titelblock']);
        $leer    = (int) !empty($daten['leer_ausblenden']);
        $layout  = in_array($daten['layout'] ?? '', ['liste'], true) ? 'liste' : 'einzelblatt';
        $summe   = (int) !empty($daten['summe']);
        $sprache = in_array($daten['sprache'] ?? '', array_keys(I18n::SPRACHEN), true)
            ? $daten['sprache'] : null;
        $istStd  = (int) !empty($daten['ist_standard']);
        $fuerEin = (int) !empty($daten['fuer_eingeschraenkte']);
        $von     = isset($daten['erstellt_von']) ? (int) $daten['erstellt_von'] : null;

        if ($istStd) {
            // Altes Standard-Profil zurücksetzen
            $pdo->exec("UPDATE export_profile SET ist_standard = 0");
        }

        if ($id === null) {
            $pdo->prepare(
                'INSERT INTO export_profile
                 (name, felder, bild, titelblock, leer_ausblenden, layout, summe, sprache,
                  ist_standard, fuer_eingeschraenkte, erstellt_von, erstellt_am, geaendert_am)
                 VALUES (:n,:f,:b,:t,:l,:la,:s,:sp,:std,:ein,:von,datetime(\'now\'),datetime(\'now\'))'
            )->execute([
                'n' => $name, 'f' => $felder, 'b' => $bild, 't' => $titel, 'l' => $leer,
                'la' => $layout, 's' => $summe, 'sp' => $sprache,
                'std' => $istStd, 'ein' => $fuerEin, 'von' => $von,
            ]);
            return (int) $pdo->lastInsertId();
        }

        $pdo->prepare(
            'UPDATE export_profile SET
             name = :n, felder = :f, bild = :b, titelblock = :t, leer_ausblenden = :l,
             layout = :la, summe = :s, sprache = :sp,
             ist_standard = :std, fuer_eingeschraenkte = :ein, geaendert_am = datetime(\'now\')
             WHERE id = :id'
        )->execute([
            'n' => $name, 'f' => $felder, 'b' => $bild, 't' => $titel, 'l' => $leer,
            'la' => $layout, 's' => $summe, 'sp' => $sprache,
            'std' => $istStd, 'ein' => $fuerEin, 'id' => $id,
        ]);
        return $id;
    }

    /** Profil duplizieren – gibt die ID des neuen Profils zurück. */
    public static function duplizieren(PDO $pdo, int $id, int $benutzerId): int
    {
        $profil = self::finde($pdo, $id);
        if ($profil === null) {
            return 0;
        }
        $daten = [
            'name'               => mb_substr($profil['name'], 0, 79) . \t('profil.kopie_suffix'),
            'felder'             => array_map('trim', explode(',', (string) $profil['felder'])),
            'bild'               => $profil['bild'],
            'titelblock'         => $profil['titelblock'],
            'leer_ausblenden'    => $profil['leer_ausblenden'],
            'layout'             => $profil['layout'],
            'summe'              => $profil['summe'],
            'sprache'            => $profil['sprache'],
            'ist_standard'       => 0,
            'fuer_eingeschraenkte' => $profil['fuer_eingeschraenkte'],
            'erstellt_von'       => $benutzerId,
        ];
        return self::speichern($pdo, $daten);
    }

    /** Profil löschen. */
    public static function loeschen(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM export_profile WHERE id = :id')->execute(['id' => $id]);
    }

    // ------------------------------------------------------ Validierung

    /**
     * Daten validieren. Gibt Fehlermeldung oder null zurück.
     * @param ?int $eigeneId Bei Aktualisierung die eigene ID für Eindeutigkeitsprüfung
     */
    public static function fehler(array $daten, PDO $pdo, ?int $eigeneId = null): ?string
    {
        $name = trim((string) ($daten['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 80) {
            return \t($name === '' ? 'profil.name_pflicht' : 'profil.name_zu_lang');
        }

        // Name eindeutig?
        $stmt = $pdo->prepare('SELECT id FROM export_profile WHERE name = :n COLLATE NOCASE');
        $stmt->execute(['n' => $name]);
        $vorhandeneId = $stmt->fetchColumn();
        if ($vorhandeneId !== false && (int) $vorhandeneId !== $eigeneId) {
            return \t('profil.name_vergeben');
        }

        $felder = self::felderAusGet((array) ($daten['felder'] ?? []));
        if ($felder === [] && empty($daten['bild'])) {
            return \t('profil.nichts_gewaehlt');
        }

        return null;
    }

    // ------------------------------- Sensible Felder im Profil erkennen

    /** Gibt true zurück wenn das Profil mindestens ein sensibles Feld enthält. */
    public static function hatSensibleFelder(array $profil): bool
    {
        $sensibel = array_column(
            array_filter(Felder::alle(), static fn(array $f): bool => $f['sensibel']),
            'key', 'key'
        );
        foreach (array_map('trim', explode(',', (string) $profil['felder'])) as $key) {
            if (isset($sensibel[$key])) {
                return true;
            }
        }
        return false;
    }
}
