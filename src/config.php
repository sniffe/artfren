<?php
declare(strict_types=1);

// Zentrale Konfiguration – Werte bei Bedarf direkt hier anpassen.

// Name der Anwendung – frei wählbar, wird in Titel, Kopfzeile und Login
// angezeigt. Hier einmal anpassen, ändert sich überall.
define('APP_NAME', 'Kunstverwaltung');

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

define('LOGIN_MAX_FEHLVERSUCHE', 4);
define('LOGIN_SPERR_MINUTEN', 15);
// Fehlversuche je IP-Adresse (alle Konten zusammen) im selben Zeitfenster.
define('LOGIN_MAX_VERSUCHE_PRO_IP', 20);
define('SITZUNG_LEERLAUF_MINUTEN', 60);

// Auf true setzen, sobald die Seite zuverlässig per HTTPS erreichbar ist:
// dann wird jeder HTTP-Aufruf auf HTTPS umgeleitet.
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
