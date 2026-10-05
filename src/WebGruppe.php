<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Zugriffsregeln und Daten-Filterung für öffentliche Gruppen-Galerien.
 *
 * Sicherheitsinvarianten:
 *   - Nur Felder mit oeffentlich='waehlbar'|'immer' werden je öffentlich.
 *   - 'beschreibung_intern' und 'status_farbe' sind immer ausgeschlossen.
 *   - Bilder nur in den Größen 'm' und 'g', nie 'o' (Original).
 *   - Passwort-Throttling: 10 Versuche / 15 Minuten je IP + Gruppe.
 */
final class WebGruppe
{
    private const THROTTLE_VERSUCHE = 10;
    private const THROTTLE_MINUTEN = 15;
    private const COOKIE_NAME = 'kv_web';

    /** True wenn die Gruppe aktiv ist und noch nicht abgelaufen. */
    public static function istZugaenglich(array $gruppe): bool
    {
        if (!(int) $gruppe['web_aktiv']) {
            return false;
        }
        $ablauf = $gruppe['web_ablauf'] ?? null;
        if ($ablauf !== null && $ablauf !== '') {
            try {
                $dt = new \DateTimeImmutable($ablauf, new \DateTimeZone('UTC'));
                if ($dt->getTimestamp() < time()) {
                    return false;
                }
            } catch (\Exception) {
                return false;
            }
        }
        return true;
    }

    /** True wenn diese Gruppe mit einem Passwort geschützt ist. */
    public static function brauchPasswort(array $gruppe): bool
    {
        return !empty($gruppe['web_passwort_hash']);
    }

    /**
     * Prüft, ob der Besucher bereits gültigen Cookie-Zugang hat.
     * Muss nach istZugaenglich() geprüft werden.
     */
    public static function hatCookieZugang(array $gruppe): bool
    {
        if (!self::brauchPasswort($gruppe)) {
            return true;
        }
        $cookie = $_COOKIE[self::COOKIE_NAME] ?? '';
        if (!is_string($cookie) || $cookie === '') {
            return false;
        }
        $erwartet = self::cookieWert((int) $gruppe['id'], (string) $gruppe['web_passwort_hash']);
        return hash_equals($erwartet, $cookie);
    }

    /**
     * Prüft ein eingegebenes Passwort, zählt Fehlversuche.
     * Gibt true zurück wenn korrekt und nicht gedrosselt.
     * Gibt false zurück wenn falsch oder gedrosselt.
     */
    public static function pruefePasswort(array $gruppe, string $passwort, string $ip, PDO $pdo): bool
    {
        if (!self::brauchPasswort($gruppe)) {
            return true;
        }

        // Throttle prüfen
        if (self::istGedrosselt($gruppe, $ip, $pdo)) {
            return false;
        }

        if (password_verify($passwort, (string) $gruppe['web_passwort_hash'])) {
            return true;
        }

        // Fehlversuch protokollieren
        $pdo->prepare(
            "INSERT INTO web_versuche (ip, gruppe_id, zeitpunkt) VALUES (:ip, :g, datetime('now'))"
        )->execute(['ip' => $ip, 'g' => (int) $gruppe['id']]);
        return false;
    }

    /** True wenn diese IP+Gruppe im Throttle-Fenster zu viele Fehlversuche hat. */
    public static function istGedrosselt(array $gruppe, string $ip, PDO $pdo): bool
    {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM web_versuche
             WHERE ip = :ip AND gruppe_id = :g
               AND zeitpunkt >= datetime('now', '-" . self::THROTTLE_MINUTEN . " minutes')"
        );
        $stmt->execute(['ip' => $ip, 'g' => (int) $gruppe['id']]);
        return (int) $stmt->fetchColumn() >= self::THROTTLE_VERSUCHE;
    }

    /** Setzt das Passwort-Cookie für diese Gruppe. */
    public static function setzeCookie(array $gruppe, string $token, bool $https): void
    {
        $wert = self::cookieWert((int) $gruppe['id'], (string) $gruppe['web_passwort_hash']);
        setcookie(self::COOKIE_NAME, $wert, [
            'expires'  => 0, // Session-Cookie
            'path'     => '/w/' . $token,
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** Bereinigt alte Throttle-Einträge (älter als 24 Stunden). */
    public static function raeumeDrosselungAuf(PDO $pdo): void
    {
        $pdo->exec("DELETE FROM web_versuche WHERE zeitpunkt < datetime('now', '-24 hours')");
    }

    /**
     * Gibt die erlaubten Feldschlüssel einer Gruppe zurück.
     * Setzt fehlende web_felder auf die Standardauswahl.
     */
    public static function erlaubteFelder(array $gruppe): array
    {
        $rohFelder = $gruppe['web_felder'] ?? null;
        if ($rohFelder !== null && $rohFelder !== '') {
            $dekodiert = json_decode((string) $rohFelder, true);
            if (is_array($dekodiert)) {
                return self::bereinigeFelderListe($dekodiert);
            }
        }
        return self::standardFelder();
    }

    /** Alle wählbaren Felder mit oeffentlich_std=true. */
    public static function standardFelder(): array
    {
        $felder = [];
        foreach (Felder::alle() as $feld) {
            if ($feld['oeffentlich'] === 'waehlbar' && $feld['oeffentlich_std']) {
                $felder[] = $feld['key'];
            }
        }
        return $felder;
    }

    /**
     * Filtert eine Werke-Zeile auf nur die erlaubten Felder.
     * Behält immer: id, maler, titel (für interne Verlinkung).
     * Behält 'copyright' wenn oeffentlich='immer' und gefüllt.
     * Gibt niemals beschreibung_intern oder status_farbe zurück.
     */
    public static function reduziereWerk(array $werk, array $erlaubteFelder): array
    {
        // Sichere Whitelist aller öffentlich zulässigen Feldschlüssel
        $immer = [];
        $waehlbar = [];
        foreach (Felder::alle() as $feld) {
            if ($feld['oeffentlich'] === 'immer') {
                $immer[] = $feld['key'];
            } elseif ($feld['oeffentlich'] === 'waehlbar') {
                $waehlbar[] = $feld['key'];
            }
            // 'never' → nie öffentlich
        }

        $erlaubt = array_unique(array_merge(
            ['id', 'maler', 'titel'],
            $immer,
            array_intersect($erlaubteFelder, $waehlbar)
        ));

        $reduziert = [];
        foreach ($erlaubt as $key) {
            if (array_key_exists($key, $werk)) {
                $reduziert[$key] = $werk[$key];
            }
        }
        return $reduziert;
    }

    /**
     * Erzeugt eine öffentliche Bild-URL für web_bild.php.
     * Nur die Größen 'm' (Karte) und 'g' (Detail) sind erlaubt.
     */
    public static function webBildUrl(string $token, ?int $bildId, string $groesse = 'm'): ?string
    {
        if ($bildId === null || $bildId <= 0) {
            return null;
        }
        $g = $groesse === 'g' ? 'g' : 'm';
        return '/web_bild.php?t=' . rawurlencode($token) . '&id=' . $bildId . '&g=' . $g;
    }

    /** Alle wählbaren Felder mit Metadaten für den Admin-Feldpicker. */
    public static function feldPickerOptionen(): array
    {
        $optionen = [];
        foreach (Felder::alle() as $feld) {
            if ($feld['oeffentlich'] !== 'waehlbar') {
                continue;
            }
            $optionen[] = $feld;
        }
        return $optionen;
    }

    // ------------------------------------------------ private Hilfsmethoden

    private static function cookieWert(int $gruppeId, string $passwortHash): string
    {
        return hash_hmac('sha256', (string) $gruppeId, $passwortHash);
    }

    /** Filtert eine Felderliste auf tatsächlich wählbare Felder. */
    private static function bereinigeFelderListe(array $liste): array
    {
        $erlaubt = [];
        foreach (Felder::alle() as $feld) {
            if ($feld['oeffentlich'] === 'waehlbar') {
                $erlaubt[] = $feld['key'];
            }
        }
        return array_values(array_intersect($liste, $erlaubt));
    }
}
