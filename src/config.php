<?php
declare(strict_types=1);

// Technische Grundkonfiguration. Muss für den Betrieb nicht angepasst werden.

// Standardwerte. Name der Anwendung und HTTPS-Zwang werden im Browser unter
// "System → Einstellungen" geändert und in der Datenbank gespeichert – ein
// Update überschreibt sie daher nicht. Diese Datei muss man nicht anpassen.
define('APP_NAME', 'Kunstverwaltung');
define('APP_VERSION', '1.2.6');

define('APP_ROOT', dirname(__DIR__));
define('DATA_PATH', APP_ROOT . '/data');
define('BILDER_PATH', APP_ROOT . '/bilder');
define('BACKUPS_PATH', APP_ROOT . '/backups');
define('CACHE_PATH', DATA_PATH . '/cache');
define('LOG_PATH', DATA_PATH . '/logs');
define('IMPORT_TMP_PATH', DATA_PATH . '/import_tmp');

// Abgeleitete Bildgrößen (Breite in Pixel): t = Listen-Thumbnail,
// m = Vitrine-Karten und PDF, g = Detailansicht.
define('BILD_GROESSEN', ['t' => 96, 'm' => 800, 'g' => 1600]);
define('BILD_ENDUNGEN', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Maximale Anzahl Bilder pro Werk (harter Grenzwert; Einstellung in Paket 6).
define('MAX_BILDER_PRO_WERK', 8);

define('LOGIN_MAX_FEHLVERSUCHE', 4);
define('LOGIN_SPERR_MINUTEN', 15);
// Fehlversuche je IP-Adresse (alle Konten zusammen) im selben Zeitfenster.
define('LOGIN_MAX_VERSUCHE_PRO_IP', 20);
define('SITZUNG_LEERLAUF_MINUTEN', 60);

// Standard für "HTTPS erzwingen" (siehe System → Einstellungen).
define('HTTPS_ERZWINGEN', false);

// Die Datenbank bekommt bei der Installation einen zufälligen Dateinamen,
// damit sie nicht erratbar ist, falls der Hoster .htaccess ignoriert.
define('DB_PATH', (static function (): string {
    $vorhanden = glob(DATA_PATH . '/kv_*.sqlite') ?: [];
    if ($vorhanden !== []) {
        return $vorhanden[0];
    }
    if (is_file(DATA_PATH . '/kunstverwaltung.sqlite')) {
        return DATA_PATH . '/kunstverwaltung.sqlite';
    }
    return DATA_PATH . '/kv_' . bin2hex(random_bytes(12)) . '.sqlite';
})());

// Zeitzone für die Anzeige; in der Datenbank wird immer UTC gespeichert.
date_default_timezone_set('Europe/Vienna');

mb_internal_encoding('UTF-8');
