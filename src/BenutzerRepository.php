<?php
declare(strict_types=1);

namespace App;

use PDO;

final class BenutzerRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public static function neu(): self
    {
        return new self(Database::get());
    }

    public function alle(): array
    {
        return $this->pdo->query('SELECT * FROM benutzer ORDER BY benutzername COLLATE NOCASE')->fetchAll();
    }

    public function finde(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM benutzer WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $benutzer = $stmt->fetch();
        return $benutzer === false ? null : $benutzer;
    }

    public function benutzernameVergeben(string $benutzername): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM benutzer WHERE benutzername = :b COLLATE NOCASE');
        $stmt->execute(['b' => $benutzername]);
        return $stmt->fetchColumn() !== false;
    }

    public function anzahlAdmins(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM benutzer WHERE rolle = 'admin'")->fetchColumn();
    }

    /** @return array<int, int[]> Benutzer-ID => Gruppen-IDs */
    public function alleZuordnungen(): array
    {
        $zuordnung = [];
        foreach ($this->pdo->query('SELECT benutzer_id, gruppe_id FROM benutzer_gruppe') as $z) {
            $zuordnung[(int) $z['benutzer_id']][] = (int) $z['gruppe_id'];
        }
        return $zuordnung;
    }

    /** @return int[] */
    public function gruppenIds(int $benutzerId): array
    {
        $stmt = $this->pdo->prepare('SELECT gruppe_id FROM benutzer_gruppe WHERE benutzer_id = :id');
        $stmt->execute(['id' => $benutzerId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function anlegen(string $benutzername, string $echterName, string $email, string $passwort, string $rolle, array $gruppenIds): int
    {
        $this->pdo->beginTransaction();
        $this->pdo->prepare(
            'INSERT INTO benutzer (benutzername, echter_name, email, passwort_hash, rolle) VALUES (:b, :n, :e, :h, :r)'
        )->execute([
            'b' => $benutzername,
            'n' => $echterName,
            'e' => $email,
            'h' => password_hash($passwort, PASSWORD_BCRYPT),
            'r' => $rolle,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        $this->schreibeGruppen($id, $rolle === 'eingeschraenkt' ? $gruppenIds : []);
        $this->pdo->commit();
        return $id;
    }

    public function aktualisieren(int $id, string $echterName, string $email, string $rolle, array $gruppenIds): void
    {
        $this->pdo->beginTransaction();
        $this->pdo->prepare('UPDATE benutzer SET echter_name = :n, email = :e, rolle = :r WHERE id = :id')
            ->execute(['n' => $echterName, 'e' => $email, 'r' => $rolle, 'id' => $id]);
        // Admins sehen ohnehin alles – Zuordnungen nur für eingeschränkte Benutzer behalten.
        $this->schreibeGruppen($id, $rolle === 'eingeschraenkt' ? $gruppenIds : []);
        $this->pdo->commit();
    }

    public function aktualisiereKontakt(int $id, string $echterName, string $email): void
    {
        $this->pdo->prepare('UPDATE benutzer SET echter_name = :n, email = :e WHERE id = :id')
            ->execute(['n' => $echterName, 'e' => $email, 'id' => $id]);
    }

    public function aktualisiereSprache(int $id, ?string $sprache): void
    {
        $this->pdo->prepare('UPDATE benutzer SET sprache = :s WHERE id = :id')
            ->execute(['s' => $sprache, 'id' => $id]);
    }

    public function aktualisiereExportRechte(int $id, int $darfExcel, int $darfBilderExport): void
    {
        $this->pdo->prepare('UPDATE benutzer SET darf_excel = :e, darf_bilder_export = :b WHERE id = :id')
            ->execute(['e' => $darfExcel, 'b' => $darfBilderExport, 'id' => $id]);
    }

    private function schreibeGruppen(int $benutzerId, array $gruppenIds): void
    {
        $this->pdo->prepare('DELETE FROM benutzer_gruppe WHERE benutzer_id = :id')->execute(['id' => $benutzerId]);
        $ins = $this->pdo->prepare('INSERT OR IGNORE INTO benutzer_gruppe (benutzer_id, gruppe_id) VALUES (:u, :g)');
        foreach ((new GruppeRepository($this->pdo))->vorhandeneIds($gruppenIds) as $gruppeId) {
            $ins->execute(['u' => $benutzerId, 'g' => $gruppeId]);
        }
    }

    public function loeschen(int $id): void
    {
        $this->pdo->prepare('DELETE FROM benutzer WHERE id = :id')->execute(['id' => $id]);
    }

    /** Prüft Stammdaten; liefert eine Fehlermeldung oder null. */
    public static function stammdatenFehler(string $echterName, string $email): ?string
    {
        if (mb_strlen($echterName) > 100) {
            return \t('benutzer.name_zu_lang');
        }
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return \t('benutzer.email_ungueltig');
        }
        return null;
    }

    public static function benutzernameFehler(string $benutzername): ?string
    {
        if (!preg_match('/^[\p{L}\p{N}._@-]{3,50}$/u', $benutzername)) {
            return \t('benutzer.name_ungueltig');
        }
        return null;
    }
}
