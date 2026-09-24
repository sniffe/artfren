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
}
