<?php
declare(strict_types=1);

namespace App;

/**
 * Im Browser änderbare Einstellungen (Tabelle "einstellungen").
 * Die Konstanten in src/config.php dienen nur noch als Standardwerte, solange
 * nichts gespeichert wurde.
 */
final class Einstellungen
{
    /** @var array<string, string>|null */
    private static ?array $werte = null;

    /** @return array<string, string> */
    private static function alle(): array
    {
        if (self::$werte === null) {
            self::$werte = [];
            // Vor der Installation bzw. vor Migration 005 gibt es die Tabelle noch nicht.
            if (Database::existiert()) {
                try {
                    self::$werte = Database::get()->query('SELECT schluessel, wert FROM einstellungen')
                        ->fetchAll(\PDO::FETCH_KEY_PAIR);
                } catch (\PDOException) {
                    self::$werte = [];
                }
            }
        }
        return self::$werte;
    }

    public static function setze(string $schluessel, string $wert): void
    {
        Database::get()->prepare(
            "INSERT INTO einstellungen (schluessel, wert) VALUES (:s, :w)
             ON CONFLICT(schluessel) DO UPDATE SET wert = excluded.wert, geaendert_am = datetime('now')"
        )->execute(['s' => $schluessel, 'w' => $wert]);
        self::$werte = null;
    }

    public static function appName(): string
    {
        $name = trim(self::alle()['app_name'] ?? '');
        return $name !== '' ? $name : APP_NAME;
    }

    public static function httpsErzwingen(): bool
    {
        $wert = self::alle()['https_erzwingen'] ?? null;
        return $wert !== null ? $wert === '1' : HTTPS_ERZWINGEN;
    }

    public static function appNameFehler(string $name): ?string
    {
        if (trim($name) === '') {
            return 'Bitte einen Namen angeben.';
        }
        if (mb_strlen($name) > 80) {
            return 'Der Name darf höchstens 80 Zeichen lang sein.';
        }
        return null;
    }
}
