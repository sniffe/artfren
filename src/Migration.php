<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Versionierte Schema-Migrationen über PRAGMA user_version.
 *
 * Dateien unter migrations/NNN_name.sql werden in Reihenfolge genau einmal
 * angewandt. Läuft automatisch beim Seitenaufruf, damit ein Update ohne
 * Shell-Zugriff nur aus dem Hochladen der neuen Dateien besteht.
 */
final class Migration
{
    /** @return array<int, string> Versionsnummer => Dateipfad */
    public static function dateien(): array
    {
        $dateien = [];
        foreach (glob(APP_ROOT . '/migrations/*.sql') ?: [] as $pfad) {
            if (preg_match('/^(\d+)_/', basename($pfad), $m)) {
                $dateien[(int) $m[1]] = $pfad;
            }
        }
        ksort($dateien);
        return $dateien;
    }

    public static function zielVersion(): int
    {
        $dateien = self::dateien();
        return $dateien === [] ? 0 : (int) array_key_last($dateien);
    }

    public static function aktuelleVersion(PDO $pdo): int
    {
        return (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    }

    public static function istAktuell(PDO $pdo): bool
    {
        return self::aktuelleVersion($pdo) >= self::zielVersion();
    }

    /** Wendet alle ausstehenden Migrationen an. Gibt die Anzahl zurück. */
    public static function aktualisiere(PDO $pdo): int
    {
        if (self::istAktuell($pdo)) {
            return 0;
        }

        self::erstelleVorMigration($pdo);

        // Fremdschlüssel müssen außerhalb der Transaktion abgeschaltet werden,
        // sonst lösen Tabellen-Neuaufbauten (DROP TABLE) Kaskaden-Löschungen aus.
        $pdo->exec('PRAGMA foreign_keys = OFF');
        $angewandt = 0;

        try {
            // IMMEDIATE sperrt sofort, damit zwei gleichzeitige Aufrufe nicht
            // dieselbe Migration doppelt ausführen.
            $pdo->exec('BEGIN IMMEDIATE');
            $version = self::aktuelleVersion($pdo);

            foreach (self::dateien() as $nummer => $pfad) {
                if ($nummer <= $version) {
                    continue;
                }
                $pdo->exec((string) file_get_contents($pfad));
                $pdo->exec('PRAGMA user_version = ' . $nummer);
                $angewandt++;
            }

            $verletzungen = $pdo->query('PRAGMA foreign_key_check')->fetchAll();
            if ($verletzungen !== []) {
                throw new \RuntimeException('Migration hat Fremdschlüssel-Verletzungen erzeugt.');
            }
            $pdo->exec('COMMIT');
        } catch (\Throwable $e) {
            try {
                $pdo->exec('ROLLBACK');
            } catch (\Throwable) {
                // Keine offene Transaktion (Fehler schon beim BEGIN).
            }
            $pdo->exec('PRAGMA foreign_keys = ON');
            throw $e;
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
        return $angewandt;
    }

    /**
     * Erstellt vor einer Migration einen konsistenten Schnappschuss der Datenbank.
     * Bricht ab und wirft eine Exception, wenn kein Schnappschuss angelegt werden kann.
     * Behält die letzten 3 Schnappschüsse und löscht ältere.
     */
    private static function erstelleVorMigration(PDO $pdo): void
    {
        $von = self::aktuelleVersion($pdo);
        $bis = self::zielVersion();
        $zeitstempel = date('Y-m-d_His');
        $ziel = DATA_PATH . "/pre-migration-{$von}-to-{$bis}-{$zeitstempel}.sqlite";

        $ok = false;
        try {
            $pdo->exec('VACUUM INTO ' . $pdo->quote($ziel));
            $ok = is_file($ziel) && filesize($ziel) > 0;
        } catch (\Throwable) {
            $ok = false;
        }

        if (!$ok) {
            $ok = @copy(DB_PATH, $ziel);
        }

        if (!$ok) {
            throw new \RuntimeException(
                'Vor der Migration konnte kein Sicherungs-Schnappschuss angelegt werden. '
                . 'Die Migration wurde nicht ausgeführt. '
                . 'Bitte stellen Sie sicher, dass der Ordner data/ schreibbar ist.'
            );
        }

        // Maximal 3 Schnappschüsse behalten – ältere löschen.
        $alle = glob(DATA_PATH . '/pre-migration-*.sqlite') ?: [];
        usort($alle, static fn(string $a, string $b): int => filemtime($a) <=> filemtime($b));
        foreach (array_slice($alle, 0, max(0, count($alle) - 3)) as $alt) {
            @unlink($alt);
        }
    }
}
