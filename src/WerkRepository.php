<?php
declare(strict_types=1);

namespace App;

use PDO;
use App\Bilder;
use App\Felder;
use App\TabellenImport;

/** Zentrale Abfragen für Kunstwerke inklusive Hauptbild. */
final class WerkRepository
{
    /**
     * Basis-SELECT für Werkabfragen. Mit $mitExtraBilder werden bild_dateiname_2..8
     * und bild_anzahl per Unterabfragen ergänzt (nur für Export nötig).
     */
    private static function basis(bool $mitExtraBilder = false): string
    {
        $extra = '(SELECT COUNT(*) FROM bilder WHERE kunstwerk_id = k.id) AS bild_anzahl';
        if ($mitExtraBilder) {
            $teile = [];
            for ($i = 2; $i <= 8; $i++) {
                $teile[] = "(SELECT b.dateiname FROM bilder b WHERE b.kunstwerk_id = k.id"
                    . " AND b.ist_hauptbild = 0 ORDER BY b.sortierung, b.id LIMIT 1 OFFSET " . ($i - 2) . ") AS bild_dateiname_{$i}";
            }
            $extra .= ', ' . implode(', ', $teile);
        }
        return "SELECT k.*, hb.id AS bild_id, hb.dateiname AS bild_dateiname, {$extra}
        FROM kunstwerke k
        LEFT JOIN bilder hb ON hb.id = (
            SELECT b.id FROM bilder b WHERE b.kunstwerk_id = k.id
            ORDER BY b.ist_hauptbild DESC, b.sortierung, b.id LIMIT 1
        )";
    }

    public const SORTIERUNGEN = [
        'ort' => 'k.ort',
        'maler' => 'k.maler',
        'titel' => 'k.titel',
        'jahr' => 'k.entstehungsjahr',
        'wert' => 'k.wert',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public static function neu(): self
    {
        return new self(Database::get());
    }

    public function finde(int $id): ?array
    {
        $stmt = $this->pdo->prepare(self::basis() . ' WHERE k.id = :id AND k.geloescht_am IS NULL');
        $stmt->execute(['id' => $id]);
        $werk = $stmt->fetch();
        return $werk === false ? null : $werk;
    }

    public function fuerGruppe(int $gruppeId): array
    {
        $stmt = $this->pdo->prepare(
            self::basis() . ' JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id
             WHERE gk.gruppe_id = :id AND k.geloescht_am IS NULL ORDER BY k.ort, k.maler, k.titel'
        );
        $stmt->execute(['id' => $gruppeId]);
        return $stmt->fetchAll();
    }

    /** @return \Generator<array> Alle Werke, speicherschonend für Exporte. */
    public function alle(): \Generator
    {
        $stmt = $this->pdo->query(self::basis() . ' WHERE k.geloescht_am IS NULL ORDER BY k.ort, k.maler, k.titel');
        while (($zeile = $stmt->fetch()) !== false) {
            yield $zeile;
        }
    }

    /**
     * @param array{q?: string, ort?: string, maler?: string, status?: string, web_freigabe?: string, ohne_bild?: string} $filter
     * @return array{0: string, 1: array}
     */
    private function where(array $filter): array
    {
        $bedingungen = ['k.geloescht_am IS NULL'];
        $params = [];

        $q = trim((string) ($filter['q'] ?? ''));
        if ($q !== '') {
            // kv_lower: umlautfähiges Kleinschreiben; % und _ im Suchtext wörtlich nehmen.
            $muster = '%' . addcslashes(mb_strtolower($q), '%_\\') . '%';
            $bedingungen[] = "(kv_lower(k.maler) LIKE :q ESCAPE '\\' OR kv_lower(k.titel) LIKE :q ESCAPE '\\' OR kv_lower(k.ort) LIKE :q ESCAPE '\\' OR kv_lower(k.technik) LIKE :q ESCAPE '\\')";
            $params['q'] = $muster;
        }
        if (($filter['ort'] ?? '') !== '') {
            $bedingungen[] = 'k.ort = :ort';
            $params['ort'] = $filter['ort'];
        }
        if (($filter['maler'] ?? '') !== '') {
            $bedingungen[] = 'k.maler = :maler';
            $params['maler'] = $filter['maler'];
        }
        $status = $filter['status'] ?? '';
        if ($status === 'ohne') {
            $bedingungen[] = 'k.status_farbe IS NULL';
        } elseif (isset(Helpers::STATUS_FARBEN[$status])) {
            $bedingungen[] = 'k.status_farbe = :status';
            $params['status'] = $status;
        }
        $webFreigabe = $filter['web_freigabe'] ?? '';
        if ($webFreigabe === 'ja') {
            $bedingungen[] = 'k.web_freigabe = 1';
        } elseif ($webFreigabe === 'nein') {
            $bedingungen[] = 'k.web_freigabe = 0';
        }
        if (!empty($filter['ohne_bild'])) {
            $bedingungen[] = 'NOT EXISTS (SELECT 1 FROM bilder b WHERE b.kunstwerk_id = k.id)';
        }

        return [$bedingungen ? 'WHERE ' . implode(' AND ', $bedingungen) : '', $params];
    }

    public function zaehle(array $filter): int
    {
        [$where, $params] = $this->where($filter);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM kunstwerke k {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** @return int[] */
    public function ids(array $filter): array
    {
        [$where, $params] = $this->where($filter);
        $stmt = $this->pdo->prepare("SELECT k.id FROM kunstwerke k {$where}");
        $stmt->execute($params);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function suche(array $filter, string $sortierung, bool $absteigend, int $limit, int $offset): array
    {
        [$where, $params] = $this->where($filter);
        $spalte = self::SORTIERUNGEN[$sortierung] ?? self::SORTIERUNGEN['ort'];
        $richtung = $absteigend ? 'DESC' : 'ASC';

        // Leere Werte immer ans Ende, unabhängig von der Richtung.
        $sql = self::basis() . " {$where}
            ORDER BY ({$spalte} IS NULL OR {$spalte} = ''), {$spalte} {$richtung}, k.ort, k.maler, k.titel
            LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $name => $wert) {
            $stmt->bindValue($name, $wert);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return string[] */
    public function orte(): array
    {
        return $this->pdo->query("SELECT DISTINCT ort FROM kunstwerke WHERE ort IS NOT NULL AND ort <> '' AND geloescht_am IS NULL ORDER BY ort")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return string[] */
    public function maler(): array
    {
        return $this->pdo->query("SELECT DISTINCT maler FROM kunstwerke WHERE maler IS NOT NULL AND maler <> '' AND geloescht_am IS NULL ORDER BY maler COLLATE NOCASE")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Filtert eine ID-Liste auf tatsächlich existierende Werke. @return int[] */
    public function vorhandeneIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $stmt = $this->pdo->prepare('SELECT id FROM kunstwerke WHERE id IN (' . Helpers::platzhalter($ids) . ') AND geloescht_am IS NULL');
        $stmt->execute($ids);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Alle per Formular und Import bearbeitbaren Felder.
     * Abgeleitet von Felder::bearbeitbareKeys() – nie mehr direkt pflegen.
     * @return string[]
     */
    public static function bearbeitbareFelder(): array
    {
        return Felder::bearbeitbareKeys();
    }

    /**
     * Speichert manuelle Änderungen. Ändern sich Ort, Maler oder Titel, bleibt
     * der alte Abgleichsschlüssel erhalten, damit ein Import der unveränderten
     * Tabelle das Werk wiedererkennt statt es doppelt anzulegen.
     */
    public function aktualisieren(array $alt, array $neu): void
    {
        $this->pdo->beginTransaction();
        $felder = self::bearbeitbareFelder();
        $this->pdo->prepare(
            'UPDATE kunstwerke SET ' . implode(', ', array_map(static fn($f) => "{$f} = :{$f}", $felder))
            . ", bearbeitet_am = datetime('now') WHERE id = :id"
        )->execute(array_merge(array_intersect_key($neu, array_flip($felder)), ['id' => $alt['id']]));

        $alterSchluessel = TabellenImport::abgleichsschluessel($alt);
        if ($alterSchluessel !== TabellenImport::abgleichsschluessel($neu)) {
            $this->pdo->prepare('INSERT OR IGNORE INTO werk_schluessel_alias (schluessel, kunstwerk_id) VALUES (:s, :id)')
                ->execute(['s' => $alterSchluessel, 'id' => $alt['id']]);
        }
        $this->pdo->commit();
    }

    /** Legt ein Werk von Hand an und liefert seine ID. */
    public function anlegen(array $daten): int
    {
        $felder = self::bearbeitbareFelder();
        // Als "im Programm bearbeitet" markiert: taucht dasselbe Werk später in
        // einer importierten Tabelle auf, wird es nicht ungefragt überschrieben.
        $this->pdo->prepare(
            'INSERT INTO kunstwerke (' . implode(', ', $felder) . ", bearbeitet_am) VALUES (:" . implode(', :', $felder) . ", datetime('now'))"
        )->execute(array_intersect_key($daten, array_flip($felder)));
        return (int) $this->pdo->lastInsertId();
    }

    /** Setzt (oder entfernt mit null) das Hauptbild eines Werks. */
    public function setzeHauptbild(int $werkId, ?string $dateiname): void
    {
        $this->pdo->beginTransaction();
        $this->pdo->prepare('DELETE FROM bilder WHERE kunstwerk_id = :id AND ist_hauptbild = 1')->execute(['id' => $werkId]);
        if ($dateiname !== null) {
            $this->pdo->prepare('INSERT INTO bilder (kunstwerk_id, dateiname, ist_hauptbild, sortierung) VALUES (:id, :d, 1, 0)')
                ->execute(['id' => $werkId, 'd' => $dateiname]);
        }
        $this->pdo->prepare("UPDATE kunstwerke SET bearbeitet_am = datetime('now') WHERE id = :id")->execute(['id' => $werkId]);
        $this->pdo->commit();
    }

    /** @return int[] Nur nicht-gelöschte Mitglieder. */
    public function mitgliedIds(int $gruppeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT gk.kunstwerk_id FROM gruppe_kunstwerk gk
             JOIN kunstwerke k ON k.id = gk.kunstwerk_id
             WHERE gk.gruppe_id = :id AND k.geloescht_am IS NULL'
        );
        $stmt->execute(['id' => $gruppeId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Alle Bilder eines Werks, sortiert nach Hauptbild → sortierung → id. */
    public function bilder(int $werkId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM bilder WHERE kunstwerk_id = :id ORDER BY ist_hauptbild DESC, sortierung, id'
        );
        $stmt->execute(['id' => $werkId]);
        return $stmt->fetchAll();
    }

    /** Fügt ein weiteres Bild (kein Hauptbild) hinzu und liefert die neue ID. */
    public function bildHinzufuegen(int $werkId, string $dateiname, ?string $beschriftung, int $sortierung): int
    {
        $this->pdo->prepare(
            'INSERT INTO bilder (kunstwerk_id, dateiname, ist_hauptbild, sortierung, beschriftung) VALUES (:id, :d, 0, :s, :b)'
        )->execute(['id' => $werkId, 'd' => $dateiname, 's' => $sortierung, 'b' => $beschriftung]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Entfernt einen Bild-Eintrag (Sicherheitsprüfung: muss zum werkId gehören). */
    public function bildEntfernen(int $bildId, int $werkId): void
    {
        $this->pdo->prepare(
            'DELETE FROM bilder WHERE id = :id AND kunstwerk_id = :wid'
        )->execute(['id' => $bildId, 'wid' => $werkId]);
        $this->normiereHauptbild($werkId);
    }

    /** Aktualisiert die Bildunterschrift. */
    public function bildBeschriftung(int $bildId, int $werkId, ?string $beschriftung): void
    {
        $this->pdo->prepare(
            'UPDATE bilder SET beschriftung = :b WHERE id = :id AND kunstwerk_id = :wid'
        )->execute(['b' => $beschriftung, 'id' => $bildId, 'wid' => $werkId]);
    }

    /**
     * Setzt die Reihenfolge der Bilder: erstes Element wird Hauptbild (ist_hauptbild=1),
     * die übrigen erhalten ist_hauptbild=0. Nur IDs, die zu werkId gehören, werden aktualisiert.
     */
    public function bildReihenfolge(int $werkId, array $sortierteIds): void
    {
        $this->pdo->beginTransaction();
        $this->pdo->prepare('UPDATE bilder SET ist_hauptbild = 0 WHERE kunstwerk_id = :id')
            ->execute(['id' => $werkId]);
        $update = $this->pdo->prepare(
            'UPDATE bilder SET sortierung = :s, ist_hauptbild = :h WHERE id = :id AND kunstwerk_id = :wid'
        );
        foreach (array_values($sortierteIds) as $pos => $bildId) {
            $update->execute(['s' => $pos, 'h' => $pos === 0 ? 1 : 0, 'id' => (int) $bildId, 'wid' => $werkId]);
        }
        $this->pdo->commit();
    }

    /** Verschiebt ein Bild um eine Position nach oben (kleinerer sortierung). */
    public function bildNachOben(int $bildId, int $werkId): void
    {
        $ids = array_column($this->bilder($werkId), 'id');
        $pos = array_search($bildId, $ids, true);
        if ($pos === false || $pos === 0) {
            return;
        }
        [$ids[(int) $pos - 1], $ids[(int) $pos]] = [$ids[(int) $pos], $ids[(int) $pos - 1]];
        $this->bildReihenfolge($werkId, $ids);
    }

    /** Verschiebt ein Bild um eine Position nach unten (größerer sortierung). */
    public function bildNachUnten(int $bildId, int $werkId): void
    {
        $ids = array_column($this->bilder($werkId), 'id');
        $pos = array_search($bildId, $ids, true);
        if ($pos === false || $pos === count($ids) - 1) {
            return;
        }
        [$ids[(int) $pos + 1], $ids[(int) $pos]] = [$ids[(int) $pos], $ids[(int) $pos + 1]];
        $this->bildReihenfolge($werkId, $ids);
    }

    /** Stellt sicher, dass das erste verbleibende Bild das Hauptbild ist. */
    private function normiereHauptbild(int $werkId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM bilder WHERE kunstwerk_id = :id AND ist_hauptbild = 1'
        );
        $stmt->execute(['id' => $werkId]);
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }
        $stmt2 = $this->pdo->prepare(
            'SELECT id FROM bilder WHERE kunstwerk_id = :id ORDER BY sortierung, id LIMIT 1'
        );
        $stmt2->execute(['id' => $werkId]);
        $firstId = $stmt2->fetchColumn();
        if ($firstId !== false) {
            $this->pdo->prepare('UPDATE bilder SET ist_hauptbild = 1 WHERE id = :id')
                ->execute(['id' => (int) $firstId]);
        }
    }

    /** Export-Generator mit bild_dateiname_2..8 per Unterabfrage. */
    public function alleExport(): \Generator
    {
        $stmt = $this->pdo->query(
            self::basis(true) . ' WHERE k.geloescht_am IS NULL ORDER BY k.ort, k.maler, k.titel'
        );
        while (($zeile = $stmt->fetch()) !== false) {
            yield $zeile;
        }
    }

    /** Export für eine Gruppe mit bild_dateiname_2..8. */
    public function fuerGruppeExport(int $gruppeId): array
    {
        $stmt = $this->pdo->prepare(
            self::basis(true) . ' JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id
             WHERE gk.gruppe_id = :id AND k.geloescht_am IS NULL ORDER BY k.ort, k.maler, k.titel'
        );
        $stmt->execute(['id' => $gruppeId]);
        return $stmt->fetchAll();
    }

    /** Verschiebt ein Werk in den Papierkorb (Soft-Delete). */
    public function inDenPapierkorb(int $werkId, string $benutzername): void
    {
        $this->pdo->prepare(
            "UPDATE kunstwerke SET geloescht_am = datetime('now'), geloescht_von = :wer WHERE id = :id AND geloescht_am IS NULL"
        )->execute(['wer' => $benutzername, 'id' => $werkId]);
    }

    /** Alle Werke im Papierkorb, neueste zuerst. */
    public function papierkorbListe(): array
    {
        $stmt = $this->pdo->query(
            self::basis() . ' WHERE k.geloescht_am IS NOT NULL ORDER BY k.geloescht_am DESC, k.ort, k.maler, k.titel'
        );
        return $stmt->fetchAll();
    }

    /** Liefert ein gelöschtes Werk nach ID oder null. */
    public function findeImPapierkorb(int $id): ?array
    {
        $stmt = $this->pdo->prepare(self::basis() . ' WHERE k.id = :id AND k.geloescht_am IS NOT NULL');
        $stmt->execute(['id' => $id]);
        $werk = $stmt->fetch();
        return $werk === false ? null : $werk;
    }

    /** Stellt ein Werk aus dem Papierkorb wieder her. */
    public function wiederherstellen(int $werkId): void
    {
        $this->pdo->prepare(
            'UPDATE kunstwerke SET geloescht_am = NULL, geloescht_von = NULL WHERE id = :id AND geloescht_am IS NOT NULL'
        )->execute(['id' => $werkId]);
    }

    /**
     * Löscht ein Werk endgültig: entfernt DB-Eintrag, legt Tombstone an
     * und entfernt Bilddateien, die kein anderes Werk mehr referenziert.
     */
    public function endgueltigLoeschen(int $werkId, string $benutzername): void
    {
        $werk = $this->findeImPapierkorb($werkId);
        if ($werk === null) {
            return;
        }

        // Bilddatei-Referenzen vor dem Löschen ermitteln.
        $stmt = $this->pdo->prepare('SELECT DISTINCT dateiname FROM bilder WHERE kunstwerk_id = :id');
        $stmt->execute(['id' => $werkId]);
        $dateinamen = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->pdo->beginTransaction();

        // Tombstone anlegen, damit der Import dieses Werk nicht neu einliest.
        $schluessel = TabellenImport::abgleichsschluessel($werk);
        $tombstoneEinfuegen = $this->pdo->prepare(
            'INSERT INTO werk_tombstone (schluessel, geloescht_von_name) VALUES (:s, :wer)'
        );
        $tombstoneEinfuegen->execute(['s' => $schluessel, 'wer' => $benutzername]);

        // Alias-Schlüssel als Tombstone eintragen.
        $aliasStmt = $this->pdo->prepare('SELECT schluessel FROM werk_schluessel_alias WHERE kunstwerk_id = :id');
        $aliasStmt->execute(['id' => $werkId]);
        foreach ($aliasStmt->fetchAll(PDO::FETCH_COLUMN) as $alias) {
            $tombstoneEinfuegen->execute(['s' => $alias, 'wer' => $benutzername]);
        }

        // Gruppen-Zuordnungen, Bilder, Alias-Schlüssel und das Werk selbst löschen.
        $this->pdo->prepare('DELETE FROM gruppe_kunstwerk WHERE kunstwerk_id = :id')->execute(['id' => $werkId]);
        $this->pdo->prepare('DELETE FROM bilder WHERE kunstwerk_id = :id')->execute(['id' => $werkId]);
        $this->pdo->prepare('DELETE FROM werk_schluessel_alias WHERE kunstwerk_id = :id')->execute(['id' => $werkId]);
        $this->pdo->prepare('DELETE FROM kunstwerke WHERE id = :id')->execute(['id' => $werkId]);

        $this->pdo->commit();

        // Bilddateien löschen, wenn sie kein anderes Werk mehr referenziert.
        foreach ($dateinamen as $dateiname) {
            $pruefen = $this->pdo->prepare('SELECT COUNT(*) FROM bilder WHERE dateiname = :d');
            $pruefen->execute(['d' => $dateiname]);
            if ((int) $pruefen->fetchColumn() !== 0) {
                continue;
            }
            $pfad = Bilder::originalPfad($dateiname);
            if (is_file($pfad)) {
                @unlink($pfad);
            }
            foreach (['t', 'm', 'g'] as $groesse) {
                $cache = CACHE_PATH . '/' . $groesse . '/' . sha1($dateiname) . '.jpg';
                if (is_file($cache)) {
                    @unlink($cache);
                }
            }
        }
    }
}
