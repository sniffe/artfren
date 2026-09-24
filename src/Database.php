<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function existiert(): bool
    {
        return is_file(DB_PATH);
    }

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            if (!self::existiert()) {
                throw new DatenbankFehltException('Datenbank ist noch nicht eingerichtet.');
            }
            self::$pdo = self::oeffne();
        }

        return self::$pdo;
    }

    /** Öffnet die Datenbank und legt die Datei an, falls sie fehlt (nur für Installer/CLI). */
    public static function oeffne(): PDO
    {
        $dsn = 'sqlite:' . DB_PATH;
        // Ab PHP 8.4 liefert PDO::connect die treiberspezifische Klasse.
        $pdo = method_exists(PDO::class, 'connect') ? PDO::connect($dsn) : new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        // Wartet bei gleichzeitigen Schreibzugriffen statt sofort "database is locked".
        // Bewusst kein WAL-Modus: auf Netzwerk-Dateisystemen (NFS) vieler
        // Shared-Hoster ist WAL laut SQLite-Doku nicht sicher.
        $pdo->exec('PRAGMA busy_timeout = 5000');

        // SQLites LOWER()/LIKE behandeln nur ASCII – für die Suche nach "öl" in "Öl".
        $klein = static fn(?string $s): ?string => $s === null ? null : mb_strtolower($s);
        if (method_exists($pdo, 'createFunction')) {
            $pdo->createFunction('kv_lower', $klein, 1);
        } else {
            $pdo->sqliteCreateFunction('kv_lower', $klein, 1);
        }

        self::$pdo = $pdo;
        return $pdo;
    }
}
