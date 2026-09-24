<?php
declare(strict_types=1);

namespace App;

use PDO;

final class GruppeRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public static function neu(): self
    {
        return new self(Database::get());
    }

    public function finde(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM gruppen WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $gruppe = $stmt->fetch();
        return $gruppe === false ? null : $gruppe;
    }

    /** Alle Gruppen, die der Benutzer sehen darf, mit Anzahl Werke. */
    public function sichtbarFuer(array $benutzer): array
    {
        $sql = 'SELECT g.*, (SELECT COUNT(*) FROM gruppe_kunstwerk gk WHERE gk.gruppe_id = g.id) AS anzahl_werke FROM gruppen g';
        if (Auth::isAdmin($benutzer)) {
            return $this->pdo->query($sql . ' ORDER BY g.name COLLATE NOCASE')->fetchAll();
        }
        $ids = Auth::sichtbareGruppenIds($benutzer);
        if ($ids === []) {
            return [];
        }
        $stmt = $this->pdo->prepare($sql . ' WHERE g.id IN (' . Helpers::platzhalter($ids) . ') ORDER BY g.name COLLATE NOCASE');
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    /** @return array<int, array{id: int, name: string}> */
    public function namen(): array
    {
        return $this->pdo->query('SELECT id, name FROM gruppen ORDER BY name COLLATE NOCASE')->fetchAll();
    }

    /** Filtert eine ID-Liste auf existierende Gruppen. @return int[] */
    public function vorhandeneIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $stmt = $this->pdo->prepare('SELECT id FROM gruppen WHERE id IN (' . Helpers::platzhalter($ids) . ')');
        $stmt->execute($ids);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function anlegen(string $name, int $benutzerId, array $werkIds): int
    {
        $this->pdo->beginTransaction();
        $this->pdo->prepare('INSERT INTO gruppen (name, erstellt_von) VALUES (:name, :von)')
            ->execute(['name' => $name, 'von' => $benutzerId]);
        $id = (int) $this->pdo->lastInsertId();
        $this->fuegeMitgliederEin($id, $werkIds);
        $this->pdo->commit();
        return $id;
    }

    public function setzeMitglieder(int $gruppeId, array $werkIds): void
    {
        $this->pdo->beginTransaction();
        $this->pdo->prepare('DELETE FROM gruppe_kunstwerk WHERE gruppe_id = :g')->execute(['g' => $gruppeId]);
        $this->fuegeMitgliederEin($gruppeId, $werkIds);
        $this->pdo->prepare("UPDATE gruppen SET geaendert_am = datetime('now') WHERE id = :id")->execute(['id' => $gruppeId]);
        $this->pdo->commit();
    }

    private function fuegeMitgliederEin(int $gruppeId, array $werkIds): void
    {
        // Nur existierende Werke; OR IGNORE schützt zusätzlich vor Doppelten.
        $ins = $this->pdo->prepare('INSERT OR IGNORE INTO gruppe_kunstwerk (gruppe_id, kunstwerk_id) VALUES (:g, :k)');
        foreach ((new WerkRepository($this->pdo))->vorhandeneIds($werkIds) as $werkId) {
            $ins->execute(['g' => $gruppeId, 'k' => $werkId]);
        }
    }

    public function umbenennen(int $id, string $name): void
    {
        $this->pdo->prepare("UPDATE gruppen SET name = :name, geaendert_am = datetime('now') WHERE id = :id")
            ->execute(['name' => $name, 'id' => $id]);
    }

    public function loeschen(int $id): void
    {
        $this->pdo->prepare('DELETE FROM gruppen WHERE id = :id')->execute(['id' => $id]);
    }

    /** Eingeschränkte Benutzer, die diese Gruppe sehen dürfen. */
    public function freigegebenFuer(int $gruppeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.id, b.benutzername, b.echter_name FROM benutzer b
             JOIN benutzer_gruppe bg ON bg.benutzer_id = b.id
             WHERE bg.gruppe_id = :id ORDER BY b.benutzername'
        );
        $stmt->execute(['id' => $gruppeId]);
        return $stmt->fetchAll();
    }
}
