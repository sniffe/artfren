<?php
declare(strict_types=1);

namespace App;

/** Audit-Log: wer hat wann was getan. */
final class Protokoll
{
    public static function schreibe(string $aktion, string $details = '', ?array $benutzer = null): void
    {
        $benutzer ??= Auth::currentUser();
        $stmt = Database::get()->prepare(
            'INSERT INTO protokoll (benutzer_id, benutzername, ip, aktion, details) VALUES (:id, :name, :ip, :aktion, :details)'
        );
        $stmt->execute([
            'id' => $benutzer['id'] ?? null,
            'name' => $benutzer['benutzername'] ?? null,
            'ip' => Auth::clientIp(),
            'aktion' => $aktion,
            'details' => mb_substr($details, 0, 2000),
        ]);

        // Gelegentlich alte Einträge aufräumen, damit die Tabelle nicht unbegrenzt wächst.
        if (random_int(1, 100) === 1) {
            Database::get()->exec("DELETE FROM protokoll WHERE zeitpunkt < datetime('now', '-2 years')");
        }
    }

    /** @return array<int, array> */
    public static function letzte(int $anzahl = 200, int $offset = 0): array
    {
        $stmt = Database::get()->prepare('SELECT * FROM protokoll ORDER BY id DESC LIMIT :n OFFSET :o');
        $stmt->bindValue('n', $anzahl, \PDO::PARAM_INT);
        $stmt->bindValue('o', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
