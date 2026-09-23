<?php
declare(strict_types=1);

namespace App;

final class Helpers
{
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::e(self::csrfToken()) . '">';
    }

    public static function checkCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(400);
            exit('Ungültige Anfrage (CSRF-Token fehlt oder ist abgelaufen). Bitte Seite neu laden und erneut versuchen.');
        }
    }

    public static function formatGeld(?float $wert): string
    {
        if ($wert === null) {
            return '';
        }

        return number_format($wert, 2, ',', '.') . ' €';
    }

    /**
     * Formatiert einen in der DB gespeicherten Zeitstempel (immer UTC, siehe
     * SQL-Defaults "datetime('now')") für die Anzeige in der App-Zeitzone.
     */
    public static function formatDatum(?string $isoDatum, string $format = 'd.m.Y H:i'): string
    {
        if (!$isoDatum) {
            return '';
        }

        try {
            $dt = new \DateTimeImmutable($isoDatum, new \DateTimeZone('UTC'));
            $dt = $dt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            return $dt->format($format);
        } catch (\Exception) {
            return $isoDatum;
        }
    }

    public static function flashSet(string $typ, string $nachricht): void
    {
        $_SESSION['flash'][] = ['typ' => $typ, 'nachricht' => $nachricht];
    }

    /** @return array<int, array{typ: string, nachricht: string}> */
    public static function flashGetAll(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Wandelt eine Liste von IDs in ein "?,?,?"-Platzhalter-Fragment für IN(...)-Abfragen.
     */
    public static function platzhalter(array $liste): string
    {
        return implode(',', array_fill(0, count($liste), '?'));
    }

    /**
     * Liefert die Thumbnail-URL: direkt aus dem Cache, falls bereits erzeugt,
     * sonst über thumb.php (das das Thumbnail bei diesem Aufruf erzeugt und cacht).
     */
    public static function thumbUrl(?string $dateiname): ?string
    {
        if (!$dateiname) {
            return null;
        }
        if (is_file(THUMBS_PATH . '/' . $dateiname) && is_file(BILDER_PATH . '/' . $dateiname)) {
            return '/thumbs/' . rawurlencode($dateiname);
        }
        if (is_file(BILDER_PATH . '/' . $dateiname)) {
            return '/thumb.php?f=' . rawurlencode($dateiname);
        }
        return null;
    }

    public static function bildUrl(?string $dateiname): ?string
    {
        if ($dateiname && is_file(BILDER_PATH . '/' . $dateiname)) {
            return '/bilder/' . rawurlencode($dateiname);
        }
        return null;
    }
}
