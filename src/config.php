<?php
declare(strict_types=1);

// Zentrale Konfiguration. Auf dem produktiven Hosting per .env oder
// direkt hier anpassen (z. B. abweichender DB-Pfad).

// Name der Anwendung – frei wählbar, wird in Titel, Kopfzeile und Login
// angezeigt. Hier einmal anpassen, ändert sich überall.
define('APP_NAME', 'Kunstverwaltung');

define('APP_ROOT', dirname(__DIR__));
define('DB_PATH', APP_ROOT . '/data/kunstverwaltung.sqlite');
define('BILDER_PATH', APP_ROOT . '/bilder');
define('THUMBS_PATH', APP_ROOT . '/thumbs');
define('BACKUPS_PATH', APP_ROOT . '/backups');
define('THUMB_BREITE', 96);

define('LOGIN_MAX_FEHLVERSUCHE', 4);
define('LOGIN_SPERR_MINUTEN', 15);

// Zeitzone für konsistente Zeitstempel (Anzeige und Sperr-Logik).
date_default_timezone_set('Europe/Vienna');

mb_internal_encoding('UTF-8');
