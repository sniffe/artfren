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
        $sql = 'SELECT g.*, (SELECT COUNT(*) FROM gruppe_kunstwerk gk JOIN kunstwerke k ON k.id = gk.kunstwerk_id WHERE gk.gruppe_id = g.id AND k.geloescht_am IS NULL) AS anzahl_werke FROM gruppen g';
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

    /**
     * Vorkommende Werte der angebotenen Filterlisten unter den Werken, die der Betrachter
     * sehen darf (alle Mitglieder bzw. in der Galerie nur web-freigegebene), alphabetisch,
     * mit Anzahl. Unabhängig von der aktuellen Auswahl, damit man sie erweitern kann.
     *
     * @param string[] $angebot
     * @return array<string, array<int, array{wert: string, anzahl: int}>>
     */
    public function filterWerte(int $gruppeId, array $angebot, bool $nurWeb = false): array
    {
        $werte = [];
        $web = $nurWeb ? ' AND k.web_freigabe = 1' : '';
        foreach (array_intersect(GruppenRegeln::FILTER_LISTEN, $angebot) as $feld) {   // Spaltenname aus der Whitelist
            $stmt = $this->pdo->prepare(
                "SELECT k.{$feld} AS wert, COUNT(*) AS anzahl
                 FROM kunstwerke k JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id
                 WHERE gk.gruppe_id = :g AND k.geloescht_am IS NULL{$web} AND k.{$feld} IS NOT NULL AND k.{$feld} <> ''
                 GROUP BY k.{$feld} ORDER BY kv_lower(k.{$feld})"
            );
            $stmt->execute(['g' => $gruppeId]);
            $werte[$feld] = array_map(
                static fn(array $z): array => ['wert' => (string) $z['wert'], 'anzahl' => (int) $z['anzahl']],
                $stmt->fetchAll(PDO::FETCH_ASSOC)
            );
        }
        return $werte;
    }

    /** Anzahl der (nicht gelöschten) Werke, optional mit Filterauswahl. */
    public function anzahlSichtbar(int $gruppeId, ?array $filter = null): int
    {
        $teile = GruppenRegeln::teile($this->pdo, $gruppeId, $filter);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM kunstwerke k JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id WHERE {$teile['where']}"
        );
        $stmt->execute($teile['params']);
        return (int) $stmt->fetchColumn();
    }

    /** Bild-Auswahl (Hauptbild, Anzahl) für öffentliche Werklisten. */
    private const OEFFENTLICH_SELECT = 'SELECT k.*, hb.id AS bild_id, hb.dateiname AS bild_dateiname,
                    (SELECT COUNT(*) FROM bilder WHERE kunstwerk_id = k.id) AS bild_anzahl
             FROM kunstwerke k
             JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id
             LEFT JOIN bilder hb ON hb.id = (
                 SELECT b.id FROM bilder b WHERE b.kunstwerk_id = k.id
                 ORDER BY b.ist_hauptbild DESC, b.sortierung, b.id LIMIT 1
             )';

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

    /** Sucht eine Gruppe anhand ihres öffentlichen Web-Tokens. */
    public function findePerToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM gruppen WHERE web_token = :t');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Speichert die Web-Einstellungen einer Gruppe (ohne Aktivierung/Deaktivierung
     * und ohne Link-Erneuerung – diese laufen über eigene Methoden).
     */
    public function webEinstellungenSpeichern(int $gruppeId, array $einstellungen): void
    {
        $erlaubt = ['web_titel', 'web_einleitung', 'web_felder', 'web_bilder_modus',
                    'web_passwort_hash', 'web_ablauf', 'web_einbetten_von', 'web_look'];
        $teile = [];
        $params = ['id' => $gruppeId];
        foreach ($erlaubt as $schluessel) {
            if (array_key_exists($schluessel, $einstellungen)) {
                $teile[] = "{$schluessel} = :{$schluessel}";
                $params[$schluessel] = $einstellungen[$schluessel];
            }
        }
        if ($teile === []) {
            return;
        }
        $this->pdo->prepare("UPDATE gruppen SET " . implode(', ', $teile) . " WHERE id = :id")
            ->execute($params);
    }

    /** Aktiviert die öffentliche Galerie und generiert bei Bedarf einen Token. */
    public function webAktivieren(int $gruppeId): void
    {
        // Generiert einen Token, falls noch keiner vorhanden.
        $gruppe = $this->finde($gruppeId);
        $token = ($gruppe['web_token'] ?? null) ?: bin2hex(random_bytes(16));
        $this->pdo->prepare(
            "UPDATE gruppen SET web_aktiv = 1, web_token = :t, web_veroeffentlicht_am = COALESCE(web_veroeffentlicht_am, datetime('now')) WHERE id = :id"
        )->execute(['t' => $token, 'id' => $gruppeId]);
    }

    /** Deaktiviert die öffentliche Galerie (Token bleibt erhalten). */
    public function webDeaktivieren(int $gruppeId): void
    {
        $this->pdo->prepare('UPDATE gruppen SET web_aktiv = 0 WHERE id = :id')
            ->execute(['id' => $gruppeId]);
    }

    /** Erzeugt einen neuen Token; der alte Link wird damit ungültig. */
    public function webLinkErneuern(int $gruppeId): string
    {
        $token = bin2hex(random_bytes(16));
        $this->pdo->prepare("UPDATE gruppen SET web_token = :t, web_veroeffentlicht_am = datetime('now') WHERE id = :id")
            ->execute(['t' => $token, 'id' => $gruppeId]);
        return $token;
    }

    /** Zählt web_aufrufe hoch und aktualisiert web_letzter_aufruf. */
    public function webAufrufErfassen(int $gruppeId): void
    {
        $this->pdo->prepare(
            "UPDATE gruppen SET web_aufrufe = web_aufrufe + 1, web_letzter_aufruf = datetime('now') WHERE id = :id"
        )->execute(['id' => $gruppeId]);
    }

    /**
     * Liefert alle für Web freigegebenen Werke der Gruppe in der Reihenfolge der Gruppe,
     * optional eingeschränkt durch die Filterauswahl des Galerie-Gasts.
     */
    public function werkeOeffentlich(int $gruppeId, ?array $filter = null): array
    {
        $teile = GruppenRegeln::teile($this->pdo, $gruppeId, $filter);
        $stmt = $this->pdo->prepare(
            self::OEFFENTLICH_SELECT . " WHERE {$teile['where']} AND k.web_freigabe = 1 ORDER BY {$teile['order']}"
        );
        $stmt->execute($teile['params']);
        return $stmt->fetchAll();
    }

    /** Einzelnes öffentliches Werk einer Gruppe (Sicherheitsprüfung: muss zur Gruppe gehören). */
    public function werkOeffentlich(int $gruppeId, int $werkId): ?array
    {
        $teile = GruppenRegeln::teile($this->pdo, $gruppeId);
        $stmt = $this->pdo->prepare(
            self::OEFFENTLICH_SELECT . " WHERE {$teile['where']} AND k.id = :kid AND k.web_freigabe = 1"
        );
        $stmt->execute($teile['params'] + ['kid' => $werkId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Alle Bilder eines öffentlichen Werks (Sicherheitsprüfung: Werk in der Gruppe). */
    public function bilderOeffentlich(int $gruppeId, int $werkId): array
    {
        $teile = GruppenRegeln::teile($this->pdo, $gruppeId);
        $stmt = $this->pdo->prepare(
            "SELECT b.* FROM bilder b
             JOIN kunstwerke k ON k.id = b.kunstwerk_id
             JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id
             WHERE {$teile['where']} AND b.kunstwerk_id = :kid AND k.web_freigabe = 1
             ORDER BY b.ist_hauptbild DESC, b.sortierung, b.id"
        );
        $stmt->execute($teile['params'] + ['kid' => $werkId]);
        return $stmt->fetchAll();
    }

    /** Dateiname eines öffentlich abrufbaren Bildes der Gruppe oder null (für web_bild.php). */
    public function bildOeffentlich(int $gruppeId, int $bildId): ?string
    {
        $teile = GruppenRegeln::teile($this->pdo, $gruppeId);
        $stmt = $this->pdo->prepare(
            "SELECT b.dateiname FROM bilder b
             JOIN kunstwerke k ON k.id = b.kunstwerk_id
             JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id
             WHERE {$teile['where']} AND b.id = :bid AND k.web_freigabe = 1"
        );
        $stmt->execute($teile['params'] + ['bid' => $bildId]);
        $datei = $stmt->fetchColumn();
        return $datei === false ? null : (string) $datei;
    }

    /** Anzahl der Werke und der davon für das Web freigegebenen. */
    public function webWerkStats(int $gruppeId): array
    {
        $teile = GruppenRegeln::teile($this->pdo, $gruppeId);
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS gesamt,
                SUM(CASE WHEN k.web_freigabe = 1 THEN 1 ELSE 0 END) AS freigegeben
             FROM gruppe_kunstwerk gk
             JOIN kunstwerke k ON k.id = gk.kunstwerk_id
             WHERE {$teile['where']}"
        );
        $stmt->execute($teile['params']);
        return $stmt->fetch();
    }

    /** Gibt alle Werke mit Sortierung für die Admin-Ansicht zurück. */
    public function fuerGruppeSortiert(int $gruppeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT k.*, hb.id AS bild_id, hb.dateiname AS bild_dateiname, gk.sortierung AS gruppen_sortierung
             FROM kunstwerke k
             JOIN gruppe_kunstwerk gk ON gk.kunstwerk_id = k.id
             LEFT JOIN bilder hb ON hb.id = (
                 SELECT b.id FROM bilder b WHERE b.kunstwerk_id = k.id
                 ORDER BY b.ist_hauptbild DESC, b.sortierung, b.id LIMIT 1
             )
             WHERE gk.gruppe_id = :id AND k.geloescht_am IS NULL
             ORDER BY gk.sortierung, k.ort, k.maler, k.titel'
        );
        $stmt->execute(['id' => $gruppeId]);
        return $stmt->fetchAll();
    }

    /** Setzt die Sortierreihenfolge der Werke in einer Gruppe. */
    public function setzeWerkReihenfolge(int $gruppeId, array $sortierteWerkIds): void
    {
        $update = $this->pdo->prepare(
            'UPDATE gruppe_kunstwerk SET sortierung = :s WHERE gruppe_id = :g AND kunstwerk_id = :k'
        );
        $this->pdo->beginTransaction();
        foreach (array_values($sortierteWerkIds) as $pos => $werkId) {
            $update->execute(['s' => $pos, 'g' => $gruppeId, 'k' => (int) $werkId]);
        }
        $this->pdo->commit();
    }

    /** Verschiebt ein Werk um eine Position nach oben in der Gruppenreihenfolge. */
    public function werkNachOben(int $gruppeId, int $werkId): void
    {
        $werke = $this->fuerGruppeSortiert($gruppeId);
        $ids = array_column($werke, 'id');
        $pos = array_search($werkId, $ids, true);
        if ($pos === false || $pos === 0) {
            return;
        }
        [$ids[(int) $pos - 1], $ids[(int) $pos]] = [$ids[(int) $pos], $ids[(int) $pos - 1]];
        $this->setzeWerkReihenfolge($gruppeId, $ids);
    }

    /** Verschiebt ein Werk um eine Position nach unten in der Gruppenreihenfolge. */
    public function werkNachUnten(int $gruppeId, int $werkId): void
    {
        $werke = $this->fuerGruppeSortiert($gruppeId);
        $ids = array_column($werke, 'id');
        $pos = array_search($werkId, $ids, true);
        if ($pos === false || $pos === count($ids) - 1) {
            return;
        }
        [$ids[(int) $pos + 1], $ids[(int) $pos]] = [$ids[(int) $pos], $ids[(int) $pos + 1]];
        $this->setzeWerkReihenfolge($gruppeId, $ids);
    }

    /** Gibt alle Werke frei (web_freigabe=1) für eine bestimmte Gruppe. */
    public function alleWerkeFreigeben(int $gruppeId): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE kunstwerke SET web_freigabe = 1
             WHERE id IN (SELECT kunstwerk_id FROM gruppe_kunstwerk WHERE gruppe_id = :g)
               AND geloescht_am IS NULL AND web_freigabe = 0'
        );
        $stmt->execute(['g' => $gruppeId]);
        return $stmt->rowCount();
    }
}
