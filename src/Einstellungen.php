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

    /** Liest einen beliebigen Einstellungswert; gibt $standard zurück wenn leer/ungesetzt. */
    public static function hol(string $schluessel, string $standard = ''): string
    {
        $wert = trim(self::alle()[$schluessel] ?? '');
        return $wert !== '' ? $wert : $standard;
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

    // ── Erscheinungsbild ──────────────────────────────────────────────────

    /**
     * Aktiver Look für den Admin-Bereich ('intern') oder öffentliche Galerien ('web').
     * Mögliche Werte: 'galerie' | 'archiv' | 'kontrast'
     */
    public static function look(string $kontext = 'intern'): string
    {
        $key  = $kontext === 'web' ? 'look_web' : 'look_intern';
        $wert = trim(self::alle()[$key] ?? '');
        return in_array($wert, ['galerie', 'archiv', 'kontrast'], true) ? $wert : 'galerie';
    }

    /**
     * Benutzerdefinierte Akzentfarbe als validierter #RRGGBB-Hex-String oder null.
     */
    public static function akzentfarbe(): ?string
    {
        $wert = trim(self::alle()['akzentfarbe'] ?? '');
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $wert) ? strtoupper($wert) : null;
    }

    /**
     * Textfarbe auf der Akzentfarbe (#FFFFFF oder #000000) nach WCAG AA (4.5:1).
     * Gibt '#FFFFFF' zurück wenn keine benutzerdefinierte Farbe gesetzt ist.
     */
    public static function akzentTextfarbe(): string
    {
        $farbe = self::akzentfarbe();
        if ($farbe === null) {
            return '#FFFFFF';
        }
        return self::wcagTextfarbe($farbe);
    }

    /**
     * Gewählte Schriftart: 'serif' (Standard), 'grotesk' oder 'system'.
     */
    public static function schrift(): string
    {
        $wert = trim(self::alle()['schrift'] ?? '');
        return in_array($wert, ['serif', 'grotesk', 'system'], true) ? $wert : 'serif';
    }

    /**
     * Icon-Stärke: 'regular' (Standard), 'light' oder 'bold'.
     */
    public static function iconStaerke(): string
    {
        $wert = trim(self::alle()['icon_staerke'] ?? '');
        return in_array($wert, ['regular', 'light', 'bold'], true) ? $wert : 'regular';
    }

    /**
     * Dateiname des Logos (ohne Pfad) oder null wenn kein Logo gesetzt.
     * Die Datei liegt unter DATA_PATH . '/logo/'.
     */
    public static function logoPfad(): ?string
    {
        $datei = trim(self::alle()['logo_datei'] ?? '');
        if ($datei === '' || !preg_match('/^[a-zA-Z0-9_\-]+\.(png|jpg|jpeg|webp|svg)$/i', $datei)) {
            return null;
        }
        return $datei;
    }

    // ── Private Hilfsmethoden ─────────────────────────────────────────────

    /**
     * Berechnet den WCAG-AA-konformen Textfarbwert (#FFFFFF oder #000000)
     * für eine gegebene Hintergrundfarbe.
     */
    private static function wcagTextfarbe(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r   = hexdec(substr($hex, 0, 2)) / 255;
        $g   = hexdec(substr($hex, 2, 2)) / 255;
        $b   = hexdec(substr($hex, 4, 2)) / 255;

        $linear = static fn(float $c): float => $c <= 0.04045
            ? $c / 12.92
            : (($c + 0.055) / 1.055) ** 2.4;

        $L = 0.2126 * $linear($r) + 0.7152 * $linear($g) + 0.0722 * $linear($b);

        $mitWeiss = 1.05 / ($L + 0.05);
        $mitSchwarz = ($L + 0.05) / 0.05;

        return $mitWeiss >= $mitSchwarz ? '#FFFFFF' : '#000000';
    }
}
