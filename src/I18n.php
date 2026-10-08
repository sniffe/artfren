<?php
declare(strict_types=1);

namespace App {

/**
 * Mehrsprachigkeits-Infrastruktur.
 *
 * Unterstützte Sprachen: I18n::SPRACHEN.
 * Aktive Sprache: I18n::setze() / I18n::aktiv().
 * Übersetzungen: lang/de.php, lang/en.php – je ein flaches Array 'bereich.key' => 'Text'.
 * Platzhalter: {name} in Übersetzungstexten, ersetzt durch $params['name'].
 * Plural: I18n::plural($n, 'key') sucht 'key.one' (n=1) oder 'key.other' (n≠1).
 * Fehlender Key: Fallback auf Deutsch, dann Key selbst – nie Fehler oder Leerstring.
 */
final class I18n
{
    /** Unterstützte Sprachen: Sprachcode => Anzeigename (bilingual) */
    public const SPRACHEN = ['de' => 'Deutsch', 'en' => 'English'];

    private static string $aktiv = 'de';

    /** @var array<string,string>|null */
    private static ?array $texte = null;

    /** @var array<string,string>|null */
    private static ?array $texteDE = null;

    public static function setze(string $sprache): void
    {
        $sprache = isset(self::SPRACHEN[$sprache]) ? $sprache : 'de';
        if ($sprache !== self::$aktiv) {
            self::$aktiv  = $sprache;
            self::$texte  = null;
        }
    }

    public static function aktiv(): string
    {
        return self::$aktiv;
    }

    /** @return array<string,string> */
    private static function laden(): array
    {
        if (self::$texte !== null) {
            return self::$texte;
        }
        $datei = APP_ROOT . '/lang/' . self::$aktiv . '.php';
        self::$texte = is_file($datei) ? (array) (require $datei) : [];
        return self::$texte;
    }

    /** @return array<string,string> */
    private static function ladenDE(): array
    {
        if (self::$texteDE !== null) {
            return self::$texteDE;
        }
        $datei = APP_ROOT . '/lang/de.php';
        self::$texteDE = is_file($datei) ? (array) (require $datei) : [];
        return self::$texteDE;
    }

    /**
     * Übersetzt einen Key. Platzhalter {name} werden durch $params['name'] ersetzt.
     * Fehlender Key: zuerst Deutsch, dann der Key selbst.
     *
     * @param array<string,int|string> $params
     */
    public static function t(string $key, array $params = []): string
    {
        $texte = self::laden();

        if (!isset($texte[$key])) {
            $texteDE = self::ladenDE();
            if (!isset($texteDE[$key])) {
                return $key;
            }
            $text = $texteDE[$key];
        } else {
            $text = $texte[$key];
        }

        foreach ($params as $name => $wert) {
            $text = str_replace('{' . $name . '}', (string) $wert, $text);
        }

        return $text;
    }

    /**
     * Wählt die Plural-Form: 'key.one' (n=1) oder 'key.other' (n≠1).
     *
     * @param array<string,int|string> $extras  Zusätzliche Platzhalter neben {n}
     */
    public static function plural(int $n, string $key, array $extras = []): string
    {
        return self::t($key . ($n === 1 ? '.one' : '.other'), array_merge(['n' => $n], $extras));
    }

    /**
     * Erkennt die aktive Sprache anhand der Prioritätskette.
     * Setzt bei ?lang= auch das Cookie.
     *
     * Priorität:
     * 1. Eingeloggter Benutzer ($benutzerseSprache)
     * 2. ?lang=-Parameter (wird in Cookie gespeichert)
     * 2b. Cookie kv_sprache
     * 2c. Accept-Language (de/en)
     * 3. Admin-Standard ($adminDefault)
     * 4. 'de'
     */
    public static function erkennen(?string $benutzerseSprache, string $adminDefault, bool $setzeCookie = true): string
    {
        // 1. Eingeloggter Benutzer
        if ($benutzerseSprache !== null && isset(self::SPRACHEN[$benutzerseSprache])) {
            return $benutzerseSprache;
        }

        // 2. ?lang= Parameter
        $lang = (string) ($_GET['lang'] ?? '');
        if (isset(self::SPRACHEN[$lang])) {
            if ($setzeCookie) {
                @setcookie('kv_sprache', $lang, [
                    'expires'  => time() + 365 * 86400,
                    'path'     => '/',
                    'samesite' => 'Lax',
                    'httponly' => true,
                ]);
            }
            return $lang;
        }

        // 2b. Cookie
        $cookie = (string) ($_COOKIE['kv_sprache'] ?? '');
        if (isset(self::SPRACHEN[$cookie])) {
            return $cookie;
        }

        // 2c. Accept-Language
        $accept = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        if ($accept !== '') {
            preg_match('/^([a-z]{2})/i', $accept, $m);
            $al = strtolower($m[1] ?? '');
            if (isset(self::SPRACHEN[$al])) {
                return $al;
            }
        }

        // 3. Admin-Standard
        if (isset(self::SPRACHEN[$adminDefault])) {
            return $adminDefault;
        }

        return 'de';
    }
}

} // namespace App

// ── Globale Hilfsfunktion ────────────────────────────────────────────────────

namespace {
    if (!function_exists('t')) {
        /**
         * Übersetzt einen Schlüssel in die aktive Sprache.
         * Platzhalter: {name}  →  $params['name'].
         *
         * @param array<string,int|string> $params
         */
        function t(string $key, array $params = []): string
        {
            return \App\I18n::t($key, $params);
        }
    }
}
