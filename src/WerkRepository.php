<?php
declare(strict_types=1);

namespace App;

use PDO;
use App\Felder;

/** Zentrale Abfragen für Kunstwerke inklusive Hauptbild. */
final class WerkRepository
{
    private const BASIS = "SELECT k.*, hb.id AS bild_id, hb.dateiname AS bild_dateiname
        FROM kunstwerke k
        LEFT JOIN bilder hb ON hb.id = (
            SELECT b.id FROM bilder b WHERE b.kunstwerk_id = k.id
            ORDER BY b.ist_hauptbild DESC, b.sortierung, b.id LIMIT 1
        )";

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
        $stmt = $this->pdo->prepare(self::BASIS . ' WHERE k.id = :id');
        $stmt->execute(['id' => $id]);
        $werk = $stmt->fetch();
        return $werk === false ? null : $werk;
    }

    public function fuerGruppe(int $gruppeId): array
    {
        $stmt = $this->pdo->prepare(
            self::BASIS . ' JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id
             WHERE gk.gruppe_id = :id ORDER BY k.ort, k.maler, k.titel'
        );
        $stmt->execute(['id' => $gruppeId]);
        return $stmt->fetchAll();
    }

    /** @return \Generator<array> Alle Werke, speicherschonend für Exporte. */
    public function alle(): \Generator
    {
        $stmt = $this->pdo->query(self::BASIS . ' ORDER BY k.ort, k.maler, k.titel');
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
        $bedingungen = [];
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
        $sql = self::BASIS . " {$where}
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
        return $this->pdo->query("SELECT DISTINCT ort FROM kunstwerke WHERE ort IS NOT NULL AND ort <> '' ORDER BY ort")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return string[] */
    public function maler(): array
    {
        return $this->pdo->query("SELECT DISTINCT maler FROM kunstwerke WHERE maler IS NOT NULL AND maler <> '' ORDER BY maler COLLATE NOCASE")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Filtert eine ID-Liste auf tatsächlich existierende Werke. @return int[] */
    public function vorhandeneIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $stmt = $this->pdo->prepare('SELECT id FROM kunstwerke WHERE id IN (' . Helpers::platzhalter($ids) . ')');
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

    /** @return int[] */
    public function mitgliedIds(int $gruppeId): array
    {
        $stmt = $this->pdo->prepare('SELECT kunstwerk_id FROM gruppe_kunstwerk WHERE gruppe_id = :id');
        $stmt->execute(['id' => $gruppeId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
